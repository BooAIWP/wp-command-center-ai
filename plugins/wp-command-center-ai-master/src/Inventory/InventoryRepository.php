<?php
/**
 * Normalized inventory persistence.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Inventory;

use WPCommandCenterAI\Core\Inventory\InventorySnapshot;
use WPCommandCenterAI\Master\Database\Schema;

defined( 'ABSPATH' ) || exit;

final class InventoryRepository {
	public function __construct( private Schema $schema ) {
	}

	public function synchronize( InventorySnapshot $snapshot ): bool {
		global $wpdb;

		$table    = $this->schema->table( 'inventory' );
		$existing = (string) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT checksum FROM {$table} WHERE site_id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$snapshot->site_id
			)
		);

		if ( hash_equals( $existing, $snapshot->checksum ) ) {
			return false;
		}

		$wpdb->replace(
			$table,
			array(
				'site_id'      => $snapshot->site_id,
				'checksum'     => $snapshot->checksum,
				'collected_at' => $snapshot->collected_at,
				'environment'  => wp_json_encode( $snapshot->environment ),
				'wordpress'    => wp_json_encode( $snapshot->wordpress ),
				'plugin_count' => count( $snapshot->plugins ),
				'theme_count'  => count( $snapshot->themes ),
				'updated_at'   => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s' )
		);

		$this->replace_components( $snapshot->site_id, 'plugin', $snapshot->plugins );
		$this->replace_components( $snapshot->site_id, 'theme', $snapshot->themes );

		return true;
	}

	public function find( string $site_id ): ?array {
		global $wpdb;

		$table = $this->schema->table( 'inventory' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE site_id = %s", $site_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		$row['environment'] = $this->decode_json( (string) $row['environment'] );
		$row['wordpress']   = $this->decode_json( (string) $row['wordpress'] );
		$row['plugins']     = $this->components( $site_id, 'plugin' );
		$row['themes']      = $this->components( $site_id, 'theme' );

		return $row;
	}

	public function summary(): array {
		global $wpdb;

		$inventory  = $this->schema->table( 'inventory' );
		$components = $this->schema->table( 'inventory_components' );

		return array(
			'reported_sites' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$inventory}" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'plugins'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$components} WHERE component_type = 'plugin'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'themes'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$components} WHERE component_type = 'theme'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'updates'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$components} WHERE update_version IS NOT NULL AND update_version <> ''" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	private function replace_components( string $site_id, string $type, array $components ): void {
		global $wpdb;

		$table = $this->schema->table( 'inventory_components' );
		$wpdb->delete(
			$table,
			array(
				'site_id'        => $site_id,
				'component_type' => $type,
			),
			array( '%s', '%s' )
		);

		foreach ( $components as $component ) {
			$wpdb->insert(
				$table,
				array(
					'site_id'        => $site_id,
					'component_type' => $type,
					'slug'           => sanitize_title( (string) ( $component['slug'] ?? '' ) ),
					'name'           => sanitize_text_field( (string) ( $component['name'] ?? '' ) ),
					'version'        => sanitize_text_field( (string) ( $component['version'] ?? '' ) ),
					'status'         => sanitize_key( (string) ( $component['status'] ?? 'inactive' ) ),
					'update_version' => empty( $component['update_version'] ) ? null : sanitize_text_field( (string) $component['update_version'] ),
					'metadata'       => wp_json_encode( (array) ( $component['metadata'] ?? array() ) ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}
	}

	private function components( string $site_id, string $type ): array {
		global $wpdb;

		$table = $this->schema->table( 'inventory_components' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT slug, name, version, status, update_version, metadata
				FROM {$table} WHERE site_id = %s AND component_type = %s ORDER BY name ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$site_id,
				$type
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		foreach ( $rows as &$row ) {
			$row['metadata'] = $this->decode_json( (string) $row['metadata'] );
		}
		unset( $row );

		return $rows;
	}

	private function decode_json( string $value ): array {
		$decoded = json_decode( $value, true );

		return is_array( $decoded ) ? $decoded : array();
	}
}
