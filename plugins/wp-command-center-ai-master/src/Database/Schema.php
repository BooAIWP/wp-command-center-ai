<?php
/**
 * Fleet database schema.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Database;

defined( 'ABSPATH' ) || exit;

final class Schema {
	public const VERSION = 2;

	public function migrate(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset    = $wpdb->get_charset_collate();
		$sites      = $this->table( 'fleet_sites' );
		$keys       = $this->table( 'fleet_keys' );
		$terms      = $this->table( 'fleet_terms' );
		$links      = $this->table( 'fleet_site_terms' );
		$inventory  = $this->table( 'inventory' );
		$components = $this->table( 'inventory_components' );

		dbDelta(
			"CREATE TABLE {$sites} (
				site_id varchar(36) NOT NULL,
				site_name varchar(255) NOT NULL,
				site_url varchar(2048) NOT NULL,
				metadata longtext NOT NULL,
				last_report longtext NOT NULL,
				registered_at bigint(20) unsigned NOT NULL,
				last_seen_at bigint(20) unsigned NULL,
				rotation_due_at bigint(20) unsigned NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (site_id),
				KEY last_seen_at (last_seen_at),
				KEY registered_at (registered_at)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$keys} (
				site_id varchar(36) NOT NULL,
				key_id varchar(64) NOT NULL,
				public_key text NOT NULL,
				created_at bigint(20) unsigned NOT NULL,
				retired_at bigint(20) unsigned NULL,
				is_current tinyint(1) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (site_id,key_id),
				KEY current_key (site_id,is_current),
				KEY retired_at (retired_at)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$terms} (
				term_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				taxonomy varchar(32) NOT NULL,
				slug varchar(191) NOT NULL,
				name varchar(255) NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (term_id),
				UNIQUE KEY taxonomy_slug (taxonomy,slug)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$links} (
				site_id varchar(36) NOT NULL,
				term_id bigint(20) unsigned NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (site_id,term_id),
				KEY term_id (term_id)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$inventory} (
				site_id varchar(36) NOT NULL,
				checksum char(64) NOT NULL,
				collected_at bigint(20) unsigned NOT NULL,
				environment longtext NOT NULL,
				wordpress longtext NOT NULL,
				plugin_count int(10) unsigned NOT NULL DEFAULT 0,
				theme_count int(10) unsigned NOT NULL DEFAULT 0,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (site_id),
				KEY checksum (checksum),
				KEY collected_at (collected_at)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$components} (
				site_id varchar(36) NOT NULL,
				component_type varchar(16) NOT NULL,
				slug varchar(191) NOT NULL,
				name varchar(255) NOT NULL,
				version varchar(64) NOT NULL,
				status varchar(32) NOT NULL,
				update_version varchar(64) NULL,
				metadata longtext NOT NULL,
				PRIMARY KEY  (site_id,component_type,slug),
				KEY component_lookup (component_type,slug),
				KEY update_version (update_version)
			) {$charset};"
		);

		update_option( 'wpccai_master_schema_version', self::VERSION, false );
	}

	public function table( string $name ): string {
		global $wpdb;

		return $wpdb->prefix . 'wpccai_' . $name;
	}
}
