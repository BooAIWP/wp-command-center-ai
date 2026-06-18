<?php
/**
 * Master plugin uninstall handler.
 *
 * @package WPCommandCenterAI\Master
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'wpccai_master_clients' );
delete_option( 'wpccai_master_shared_secret' );
delete_option( 'wpccai_master_version' );
delete_option( 'wpccai_master_enrollment_token' );
delete_option( 'wpccai_master_key_ring' );
delete_option( 'wpccai_master_schema_version' );
delete_option( 'wpccai_master_legacy_clients_migrated' );

global $wpdb;

foreach ( array( 'inventory_components', 'inventory', 'fleet_site_terms', 'fleet_terms', 'fleet_keys', 'fleet_sites' ) as $table ) {
	$table_name = $wpdb->prefix . 'wpccai_' . $table;
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}
