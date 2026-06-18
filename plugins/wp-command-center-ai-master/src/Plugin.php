<?php
/**
 * Master plugin bootstrap.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master;

use WPCommandCenterAI\Master\Admin\AdminPage;
use WPCommandCenterAI\Master\Rest\HeartbeatController;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function activate(): void {
		if ( false === get_option( 'wpccai_master_shared_secret', false ) ) {
			add_option( 'wpccai_master_shared_secret', wp_generate_password( 48, false, false ), '', false );
		}
	}

	public function boot(): void {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		( new AdminPage() )->register();
		( new HeartbeatController() )->register();
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wp-command-center-ai-master',
			false,
			dirname( plugin_basename( WPCCAI_MASTER_FILE ) ) . '/languages'
		);
	}
}
