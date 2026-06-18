<?php
/**
 * Master application module.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Module;

use WPCommandCenterAI\Core\Capability\Capability;
use WPCommandCenterAI\Core\Capability\CapabilityRegistry;
use WPCommandCenterAI\Core\Container\Container;
use WPCommandCenterAI\Core\Logging\LoggerInterface;
use WPCommandCenterAI\Core\Module\LifecycleModuleInterface;
use WPCommandCenterAI\Core\Rest\RestApi;
use WPCommandCenterAI\Master\Admin\AdminPage;
use WPCommandCenterAI\Master\Rest\HeartbeatController;

defined( 'ABSPATH' ) || exit;

final class MasterModule implements LifecycleModuleInterface {
	public function id(): string {
		return 'master';
	}

	public function register( Container $container ): void {
		$container->singleton( AdminPage::class, AdminPage::class );
		$container->singleton(
			HeartbeatController::class,
			static fn ( Container $container ): HeartbeatController => new HeartbeatController(
				$container->get( LoggerInterface::class )
			)
		);
	}

	public function boot( Container $container ): void {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		$container->get( AdminPage::class )->register();
		$container->get( RestApi::class )->add_provider(
			'master.heartbeat',
			$container->get( HeartbeatController::class )
		);

		$container->get( CapabilityRegistry::class )->register(
			new Capability(
				'master.heartbeat.receive',
				__( 'Receive heartbeat', 'wp-command-center-ai-master' ),
				__( 'Receive client heartbeat status reports through the REST API.', 'wp-command-center-ai-master' )
			)
		);
	}

	public function activate( Container $container ): void {
		if ( false === get_option( 'wpccai_master_shared_secret', false ) ) {
			add_option( 'wpccai_master_shared_secret', wp_generate_password( 48, false, false ), '', false );
		}

		update_option( 'wpccai_master_version', WPCCAI_MASTER_VERSION, false );
		$container->get( LoggerInterface::class )->info( 'Master module activated.' );
	}

	public function deactivate( Container $container ): void {
		$container->get( LoggerInterface::class )->info( 'Master module deactivated.' );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wp-command-center-ai-master',
			false,
			dirname( plugin_basename( WPCCAI_MASTER_FILE ) ) . '/languages'
		);
	}
}
