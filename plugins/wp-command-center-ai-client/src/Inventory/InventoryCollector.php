<?php
/**
 * WordPress inventory collector.
 *
 * @package WPCommandCenterAI\Client
 */

namespace WPCommandCenterAI\Client\Inventory;

defined( 'ABSPATH' ) || exit;

final class InventoryCollector {
	public function collect(): array {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		return array(
			'collected_at' => time(),
			'environment'  => $this->environment(),
			'wordpress'    => $this->wordpress(),
			'plugins'      => $this->plugins(),
			'themes'       => $this->themes(),
		);
	}

	private function environment(): array {
		global $wpdb;

		return array(
			'php_version'     => PHP_VERSION,
			'database_server' => $wpdb->db_server_info(),
			'server_software' => sanitize_text_field( (string) ( $_SERVER['SERVER_SOFTWARE'] ?? '' ) ),
			'multisite'       => is_multisite(),
			'environment'     => wp_get_environment_type(),
			'https'           => is_ssl(),
		);
	}

	private function wordpress(): array {
		$core_updates = get_site_transient( 'update_core' );
		$has_update   = false;

		if ( is_object( $core_updates ) && is_array( $core_updates->updates ?? null ) ) {
			foreach ( $core_updates->updates as $update ) {
				if ( is_object( $update ) && 'upgrade' === ( $update->response ?? '' ) ) {
					$has_update = true;
					break;
				}
			}
		}

		return array(
			'version'          => get_bloginfo( 'version' ),
			'locale'           => get_locale(),
			'timezone'         => wp_timezone_string(),
			'debug'            => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'automatic_updates' => ! defined( 'AUTOMATIC_UPDATER_DISABLED' ) || ! AUTOMATIC_UPDATER_DISABLED,
			'update_available' => $has_update,
		);
	}

	private function plugins(): array {
		$plugins = get_plugins();
		$active  = array_flip( (array) get_option( 'active_plugins', array() ) );
		$updates = get_site_transient( 'update_plugins' );
		$result  = array();

		foreach ( $plugins as $file => $plugin ) {
			$slug     = dirname( $file );
			$slug     = '.' === $slug ? basename( $file, '.php' ) : $slug;
			$update   = is_object( $updates ) && isset( $updates->response[ $file ] ) ? $updates->response[ $file ] : null;
			$result[] = array(
				'slug'           => $slug,
				'name'           => (string) ( $plugin['Name'] ?? $slug ),
				'version'        => (string) ( $plugin['Version'] ?? '' ),
				'status'         => isset( $active[ $file ] ) || is_plugin_active_for_network( $file ) ? 'active' : 'inactive',
				'update_version' => is_object( $update ) ? (string) ( $update->new_version ?? '' ) : null,
				'metadata'       => array(
					'network' => ! empty( $plugin['Network'] ),
					'file'    => $file,
				),
			);
		}

		return $result;
	}

	private function themes(): array {
		$themes  = wp_get_themes();
		$active  = get_stylesheet();
		$updates = get_site_transient( 'update_themes' );
		$result  = array();

		foreach ( $themes as $slug => $theme ) {
			$update   = is_object( $updates ) && isset( $updates->response[ $slug ] ) ? $updates->response[ $slug ] : null;
			$result[] = array(
				'slug'           => $slug,
				'name'           => $theme->get( 'Name' ),
				'version'        => $theme->get( 'Version' ),
				'status'         => $slug === $active ? 'active' : 'inactive',
				'update_version' => is_array( $update ) ? (string) ( $update['new_version'] ?? '' ) : null,
				'metadata'       => array(
					'template' => $theme->get_template(),
				),
			);
		}

		return $result;
	}
}
