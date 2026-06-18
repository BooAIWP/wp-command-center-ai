<?php
/**
 * Client application module.
 *
 * @package WPCommandCenterAI\Client
 */

namespace WPCommandCenterAI\Client\Module;

use WPCommandCenterAI\Client\Admin\SettingsPage;
use WPCommandCenterAI\Client\Plugin;
use WPCommandCenterAI\Client\Service\Heartbeat;
use WPCommandCenterAI\Core\Capability\Capability;
use WPCommandCenterAI\Core\Capability\CapabilityRegistry;
use WPCommandCenterAI\Core\Container\Container;
use WPCommandCenterAI\Core\Logging\LoggerInterface;
use WPCommandCenterAI\Core\Module\LifecycleModuleInterface;

defined( 'ABSPATH' ) || exit;

final class ClientModule implements LifecycleModuleInterface {
	public function id(): string {
		return 'client';
	}

	public function register( Container $container ): void {
		$container->singleton( SettingsPage::class, SettingsPage::class );
		$container->singleton(
			Heartbeat::class,
			static fn ( Container $container ): Heartbeat => new Heartbeat(
				$container->get( LoggerInterface::class )
			)
		);
	}

	public function boot( Container $container ): void {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( Plugin::CRON_HOOK, array( $container->get( Heartbeat::class ), 'send' ) );

		$container->get( SettingsPage::class )->register();
		$container->get( CapabilityRegistry::class )->register(
			new Capability(
				'client.heartbeat.send',
				__( 'Send heartbeat', 'wp-command-center-ai-client' ),
				__( 'Send scheduled status reports to the Master site.', 'wp-command-center-ai-client' )
			)
		);
	}

	public function activate( Container $container ): void {
		if ( false === get_option( 'wpccai_client_site_id', false ) ) {
			add_option( 'wpccai_client_site_id', wp_generate_uuid4(), '', false );
		}

		if ( ! wp_next_scheduled( Plugin::CRON_HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', Plugin::CRON_HOOK );
		}

		update_option( 'wpccai_client_version', WPCCAI_CLIENT_VERSION, false );
		$container->get( LoggerInterface::class )->info( 'Client module activated.' );
	}

	public function deactivate( Container $container ): void {
		wp_clear_scheduled_hook( Plugin::CRON_HOOK );
		$container->get( LoggerInterface::class )->info( 'Client module deactivated.' );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wp-command-center-ai-client',
			false,
			dirname( plugin_basename( WPCCAI_CLIENT_FILE ) ) . '/languages'
		);
	}
}
