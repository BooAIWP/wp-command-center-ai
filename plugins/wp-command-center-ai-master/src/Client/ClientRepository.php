<?php
/**
 * Registered client persistence.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Client;

use WPCommandCenterAI\Core\Fleet\FleetQuery;
use WPCommandCenterAI\Core\Status\ClientStatusDetector;
use WPCommandCenterAI\Core\Security\RotationPolicy;
use WPCommandCenterAI\Master\Database\Schema;

defined( 'ABSPATH' ) || exit;

final class ClientRepository {
	public function __construct(
		private ClientStatusDetector $status_detector,
		private Schema $schema
	) {
	}

	public function all(): array {
		return $this->query( new FleetQuery( limit: 10000 ) );
	}

	public function query( FleetQuery $query ): array {
		global $wpdb;

		$sites_table = $this->schema->table( 'fleet_sites' );
		$sql         = "SELECT s.* FROM {$sites_table} s WHERE 1=1";
		$args        = array();

		if ( ! empty( $query->site_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $query->site_ids ), '%s' ) );
			$sql         .= " AND s.site_id IN ({$placeholders})";
			$args         = array_merge( $args, array_map( 'strval', $query->site_ids ) );
		}

		foreach ( array( 'group' => $query->groups, 'tag' => $query->tags ) as $taxonomy => $slugs ) {
			if ( empty( $slugs ) ) {
				continue;
			}

			$terms_table = $this->schema->table( 'fleet_terms' );
			$links_table = $this->schema->table( 'fleet_site_terms' );
			$placeholders = implode( ',', array_fill( 0, count( $slugs ), '%s' ) );
			$sql         .= " AND EXISTS (
				SELECT 1 FROM {$links_table} l
				INNER JOIN {$terms_table} t ON t.term_id = l.term_id
				WHERE l.site_id = s.site_id AND t.taxonomy = %s AND t.slug IN ({$placeholders})
			)";
			$args[]       = $taxonomy;
			$args         = array_merge( $args, array_map( 'sanitize_title', $slugs ) );
		}

		if ( ! empty( $query->capabilities ) ) {
			$capabilities_table = $this->schema->table( 'capabilities' );
			$placeholders       = implode( ',', array_fill( 0, count( $query->capabilities ), '%s' ) );
			$sql               .= " AND s.site_id IN (
				SELECT c.site_id FROM {$capabilities_table} c
				WHERE c.negotiated = 1 AND c.capability_id IN ({$placeholders})
				GROUP BY c.site_id
				HAVING COUNT(DISTINCT c.capability_id) = %d
			)";
			$args               = array_merge(
				$args,
				array_map( array( $this, 'normalize_capability_id' ), $query->capabilities )
			);
			$args[]             = count( array_unique( $query->capabilities ) );
		}

		$sql .= ' ORDER BY s.site_name ASC LIMIT %d OFFSET %d';
		$args[] = max( 1, min( 10000, $query->limit ) );
		$args[] = max( 0, $query->offset );

		$prepared = $wpdb->prepare( $sql, $args ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows     = $wpdb->get_results( $prepared, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array_map( array( $this, 'hydrate' ), is_array( $rows ) ? $rows : array() );
	}

	public function find( string $site_id ): ?array {
		global $wpdb;

		$table = $this->schema->table( 'fleet_sites' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE site_id = %s", $site_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return is_array( $row ) ? $this->hydrate( $row ) : null;
	}

	public function register( array $registration ): array {
		global $wpdb;

		$site_id = wp_generate_uuid4();
		$now     = time();
		$site    = array(
			'site_id'         => $site_id,
			'site_name'       => sanitize_text_field( (string) $registration['site_name'] ),
			'site_url'        => esc_url_raw( (string) $registration['site_url'] ),
			'metadata'        => array(),
			'registered_at'   => $now,
			'last_seen_at'    => null,
			'last_report'     => array(),
			'rotation_due_at' => $now + RotationPolicy::DEFAULT_INTERVAL,
		);

		$this->upsert_site( $site );
		$this->store_key(
			$site_id,
			array(
				'key_id'      => sanitize_text_field( (string) $registration['key_id'] ),
				'public_key'  => sanitize_text_field( (string) $registration['public_key'] ),
				'created_at'  => $now,
				'retired_at'  => null,
				'is_current'  => 1,
			)
		);

		$client = $this->find( $site_id );

		return is_array( $client ) ? $client : array();
	}

	public function record_heartbeat( string $site_id, array $report ): bool {
		global $wpdb;

		$client = $this->find( $site_id );

		if ( null === $client ) {
			return false;
		}

		$now = time();
		$wpdb->update(
			$this->schema->table( 'fleet_sites' ),
			array(
				'site_name'      => sanitize_text_field( (string) ( $report['site_name'] ?? $client['site_name'] ) ),
				'site_url'       => esc_url_raw( (string) ( $report['site_url'] ?? $client['site_url'] ) ),
				'last_seen_at'   => $now,
				'last_report'    => wp_json_encode( $this->sanitize_report( $report ) ),
				'updated_at'     => gmdate( 'Y-m-d H:i:s', $now ),
			),
			array( 'site_id' => $site_id ),
			array( '%s', '%s', '%d', '%s', '%s' ),
			array( '%s' )
		);

		$next_key_id     = sanitize_text_field( (string) ( $report['next_key_id'] ?? '' ) );
		$next_public_key = sanitize_text_field( (string) ( $report['next_public_key'] ?? '' ) );

		if ( '' !== $next_key_id && '' !== $next_public_key ) {
			$current_key_id = (string) $client['current_key_id'];

			if ( $current_key_id !== $next_key_id ) {
				$keys_table = $this->schema->table( 'fleet_keys' );
				$wpdb->update(
					$keys_table,
					array(
						'is_current' => 0,
						'retired_at' => $now,
					),
					array(
						'site_id'    => $site_id,
						'is_current' => 1,
					),
					array( '%d', '%d' ),
					array( '%s', '%d' )
				);
				$this->store_key(
					$site_id,
					array(
						'key_id'      => $next_key_id,
						'public_key'  => $next_public_key,
						'created_at'  => $now,
						'retired_at'  => null,
						'is_current'  => 1,
					)
				);
				$wpdb->update(
					$this->schema->table( 'fleet_sites' ),
					array( 'rotation_due_at' => $now + RotationPolicy::DEFAULT_INTERVAL ),
					array( 'site_id' => $site_id ),
					array( '%d' ),
					array( '%s' )
				);
			}
		}

		return true;
	}

	public function public_key( string $site_id, string $key_id ): ?string {
		global $wpdb;

		$table = $this->schema->table( 'fleet_keys' );
		$key   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT public_key, retired_at FROM {$table} WHERE site_id = %s AND key_id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site_id,
				$key_id
			),
			ARRAY_A
		);

		if ( ! is_array( $key ) ) {
			return null;
		}

		$retired_at = absint( $key['retired_at'] ?? 0 );

		if ( 0 !== $retired_at && ( new RotationPolicy() )->grace_expired( $retired_at ) ) {
			return null;
		}

		return isset( $key['public_key'] ) ? (string) $key['public_key'] : null;
	}

	public function set_metadata( string $site_id, array $metadata ): bool {
		global $wpdb;

		return false !== $wpdb->update(
			$this->schema->table( 'fleet_sites' ),
			array(
				'metadata'   => wp_json_encode( $this->sanitize_metadata( $metadata ) ),
				'updated_at' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( 'site_id' => $site_id ),
			array( '%s', '%s' ),
			array( '%s' )
		);
	}

	public function set_terms( string $site_id, string $taxonomy, array $terms ): void {
		global $wpdb;

		if ( ! in_array( $taxonomy, array( 'group', 'tag' ), true ) ) {
			return;
		}

		$terms_table = $this->schema->table( 'fleet_terms' );
		$links_table = $this->schema->table( 'fleet_site_terms' );
		$term_ids    = array();

		foreach ( $terms as $term ) {
			$name = sanitize_text_field( (string) $term );
			$slug = sanitize_title( $name );

			if ( '' === $slug ) {
				continue;
			}

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$terms_table} (taxonomy, slug, name, created_at)
					VALUES (%s, %s, %s, %s)
					ON DUPLICATE KEY UPDATE name = VALUES(name)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$taxonomy,
					$slug,
					$name,
					gmdate( 'Y-m-d H:i:s' )
				)
			);
			$term_ids[] = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT term_id FROM {$terms_table} WHERE taxonomy = %s AND slug = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$taxonomy,
					$slug
				)
			);
		}

		$existing_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT l.term_id FROM {$links_table} l
				INNER JOIN {$terms_table} t ON t.term_id = l.term_id
				WHERE l.site_id = %s AND t.taxonomy = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site_id,
				$taxonomy
			)
		);

		foreach ( array_diff( array_map( 'intval', $existing_ids ), $term_ids ) as $term_id ) {
			$wpdb->delete( $links_table, array( 'site_id' => $site_id, 'term_id' => $term_id ), array( '%s', '%d' ) );
		}

		foreach ( array_unique( $term_ids ) as $term_id ) {
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$links_table} (site_id, term_id, created_at) VALUES (%s, %d, %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$site_id,
					$term_id,
					gmdate( 'Y-m-d H:i:s' )
				)
			);
		}
	}

	public function import_legacy( string $site_id, array $client ): void {
		$site = array(
			'site_id'         => $site_id,
			'site_name'       => (string) ( $client['site_name'] ?? $client['site_url'] ?? $site_id ),
			'site_url'        => (string) ( $client['site_url'] ?? '' ),
			'metadata'        => (array) ( $client['metadata'] ?? array() ),
			'registered_at'   => absint( $client['registered_at'] ?? time() ),
			'last_seen_at'    => $this->parse_timestamp( $client['last_seen_at'] ?? $client['last_seen'] ?? null ),
			'last_report'     => (array) ( $client['last_report'] ?? $client ),
			'rotation_due_at' => absint( $client['rotation_due_at'] ?? 0 ),
		);

		$this->upsert_site( $site );

		foreach ( (array) ( $client['public_keys'] ?? array() ) as $key_id => $key ) {
			$key_data = is_array( $key ) ? $key : array( 'public_key' => $key );
			$this->store_key(
				$site_id,
				array(
					'key_id'      => (string) $key_id,
					'public_key'  => (string) ( $key_data['public_key'] ?? '' ),
					'created_at'  => absint( $key_data['created_at'] ?? time() ),
					'retired_at'  => empty( $key_data['retired_at'] ) ? null : absint( $key_data['retired_at'] ),
					'is_current'  => (string) $key_id === (string) ( $client['current_key_id'] ?? '' ) ? 1 : 0,
				)
			);
		}
	}

	public function status( array $client, ?int $now = null ): string {
		$last_seen = absint( $client['last_seen_at'] ?? 0 );

		return $this->status_detector->detect( 0 === $last_seen ? null : $last_seen, $now );
	}

	public function counts( ?int $now = null ): array {
		$counts = array(
			'online'  => 0,
			'stale'   => 0,
			'offline' => 0,
		);

		foreach ( $this->all() as $client ) {
			++$counts[ $this->status( $client, $now ) ];
		}

		return $counts;
	}

	private function sanitize_report( array $report ): array {
		return array(
			'wp_version'     => sanitize_text_field( (string) ( $report['wp_version'] ?? '' ) ),
			'php_version'    => sanitize_text_field( (string) ( $report['php_version'] ?? '' ) ),
			'client_version' => sanitize_text_field( (string) ( $report['client_version'] ?? '' ) ),
		);
	}

	private function sanitize_metadata( array $metadata ): array {
		$clean = array();

		foreach ( $metadata as $key => $value ) {
			$key = sanitize_key( (string) $key );

			if ( '' !== $key && ( is_scalar( $value ) || null === $value ) ) {
				$clean[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		return $clean;
	}

	private function upsert_site( array $site ): void {
		global $wpdb;

		$now = gmdate( 'Y-m-d H:i:s' );
		$wpdb->replace(
			$this->schema->table( 'fleet_sites' ),
			array(
				'site_id'         => (string) $site['site_id'],
				'site_name'       => sanitize_text_field( (string) $site['site_name'] ),
				'site_url'        => esc_url_raw( (string) $site['site_url'] ),
				'metadata'        => wp_json_encode( $this->sanitize_metadata( (array) $site['metadata'] ) ),
				'last_report'     => wp_json_encode( (array) $site['last_report'] ),
				'registered_at'   => absint( $site['registered_at'] ),
				'last_seen_at'    => null === $site['last_seen_at'] ? null : absint( $site['last_seen_at'] ),
				'rotation_due_at' => empty( $site['rotation_due_at'] ) ? null : absint( $site['rotation_due_at'] ),
				'created_at'      => $now,
				'updated_at'      => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
		);
	}

	private function store_key( string $site_id, array $key ): void {
		global $wpdb;

		if ( empty( $key['key_id'] ) || empty( $key['public_key'] ) ) {
			return;
		}

		$wpdb->replace(
			$this->schema->table( 'fleet_keys' ),
			array(
				'site_id'    => $site_id,
				'key_id'     => sanitize_text_field( (string) $key['key_id'] ),
				'public_key' => sanitize_text_field( (string) $key['public_key'] ),
				'created_at' => absint( $key['created_at'] ?? time() ),
				'retired_at' => empty( $key['retired_at'] ) ? null : absint( $key['retired_at'] ),
				'is_current' => empty( $key['is_current'] ) ? 0 : 1,
			),
			array( '%s', '%s', '%s', '%d', '%d', '%d' )
		);
	}

	private function hydrate( array $row ): array {
		$site_id = (string) $row['site_id'];

		return array(
			'site_id'         => $site_id,
			'site_name'       => (string) $row['site_name'],
			'site_url'        => (string) $row['site_url'],
			'metadata'        => $this->decode_json( (string) $row['metadata'] ),
			'groups'          => $this->terms( $site_id, 'group' ),
			'tags'            => $this->terms( $site_id, 'tag' ),
			'current_key_id'  => $this->current_key_id( $site_id ),
			'registered_at'   => absint( $row['registered_at'] ),
			'last_seen_at'    => empty( $row['last_seen_at'] ) ? null : absint( $row['last_seen_at'] ),
			'last_report'     => $this->decode_json( (string) $row['last_report'] ),
			'rotation_due_at' => empty( $row['rotation_due_at'] ) ? null : absint( $row['rotation_due_at'] ),
		);
	}

	private function current_key_id( string $site_id ): string {
		global $wpdb;

		$table = $this->schema->table( 'fleet_keys' );

		return (string) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT key_id FROM {$table} WHERE site_id = %s AND is_current = 1 LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site_id
			)
		);
	}

	private function terms( string $site_id, string $taxonomy ): array {
		global $wpdb;

		$terms = $this->schema->table( 'fleet_terms' );
		$links = $this->schema->table( 'fleet_site_terms' );

		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT t.name FROM {$terms} t INNER JOIN {$links} l ON l.term_id = t.term_id
				WHERE l.site_id = %s AND t.taxonomy = %s ORDER BY t.name ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site_id,
				$taxonomy
			)
		);
	}

	private function decode_json( string $value ): array {
		$decoded = json_decode( $value, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	private function parse_timestamp( mixed $value ): ?int {
		if ( empty( $value ) ) {
			return null;
		}

		if ( is_numeric( $value ) ) {
			return absint( $value );
		}

		$timestamp = strtotime( (string) $value );

		return false === $timestamp ? null : $timestamp;
	}

	private function normalize_capability_id( mixed $capability_id ): string {
		return (string) preg_replace( '/[^a-z0-9._-]/', '', strtolower( (string) $capability_id ) );
	}
}
