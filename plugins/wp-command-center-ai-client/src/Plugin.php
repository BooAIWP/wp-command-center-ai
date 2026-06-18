<?php
/**
 * Client plugin bootstrap.
 *
 * @package WPCommandCenterAI\Client
 */

namespace WPCommandCenterAI\Client;

use WPCommandCenterAI\Client\Admin\SettingsPage;
use WPCommandCenterAI\Client\Service\Heartbeat;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	public const CRON_HOOK = 'wpccai_client_heartbeat';

	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function activate(): void {
		if ( false === get_option( 'wpccai_client_site_id', false ) ) {
			add_option( 'wpccai_client_site_id', wp_generate_uuid4(), '', false );
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', self::CRON_HOOK );
		}
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	public function boot(): void {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( self::CRON_HOOK, array( new Heartbeat(), 'send' ) );
		( new SettingsPage() )->register();
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wp-command-center-ai-client',
			false,
			dirname( plugin_basename( WPCCAI_CLIENT_FILE ) ) . '/languages'
		);
	}
}
