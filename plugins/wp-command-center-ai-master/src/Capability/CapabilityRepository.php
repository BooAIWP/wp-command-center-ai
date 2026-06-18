<?php
/**
 * Negotiated capability persistence.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Capability;

use WPCommandCenterAI\Core\Capability\CapabilityManifest;
use WPCommandCenterAI\Master\Database\Schema;

defined( 'ABSPATH' ) || exit;

final class CapabilityRepository {
	public function __construct( private Schema $schema ) {
	}

	public function synchronize( CapabilityManifest $manifest, array $negotiated ): void {
		global $wpdb;

		$table = $this->schema->table( 'capabilities' );
		$wpdb->delete( $table, array( 'site_id' => $manifest->site_id ), array( '%s' ) );

		foreach ( $manifest->capabilities as $capability_id => $version ) {
			$wpdb->insert(
				$table,
				array(
					'site_id'       => $manifest->site_id,
					'capability_id' => $this->normalize_id( $capability_id ),
					'version'       => sanitize_text_field( (string) $version ),
					'negotiated'    => isset( $negotiated[ $capability_id ] ) ? 1 : 0,
					'reported_at'   => $manifest->generated_at,
				),
				array( '%s', '%s', '%s', '%d', '%d' )
			);
		}
	}

	public function for_site( string $site_id, bool $negotiated_only = false ): array {
		global $wpdb;

		$table = $this->schema->table( 'capabilities' );
		$sql   = "SELECT capability_id, version, negotiated, reported_at FROM {$table} WHERE site_id = %s";

		if ( $negotiated_only ) {
			$sql .= ' AND negotiated = 1';
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare( $sql, $site_id ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	public function supports( string $site_id, string $capability_id, ?string $minimum_version = null ): bool {
		global $wpdb;

		$table   = $this->schema->table( 'capabilities' );
		$version = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT version FROM {$table}
				WHERE site_id = %s AND capability_id = %s AND negotiated = 1 LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site_id,
				$capability_id
			)
		);

		if ( null === $version ) {
			return false;
		}

		return null === $minimum_version || version_compare( (string) $version, $minimum_version, '>=' );
	}

	public function summary(): array {
		global $wpdb;

		$table = $this->schema->table( 'capabilities' );
		$rows  = $wpdb->get_results(
			"SELECT capability_id, version, COUNT(*) AS site_count
			FROM {$table} WHERE negotiated = 1
			GROUP BY capability_id, version ORDER BY capability_id ASC, version DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	private function normalize_id( string $capability_id ): string {
		return (string) preg_replace( '/[^a-z0-9._-]/', '', strtolower( $capability_id ) );
	}
}
