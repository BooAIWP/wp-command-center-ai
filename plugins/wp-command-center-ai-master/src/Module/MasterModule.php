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
use WPCommandCenterAI\Core\Status\ClientStatusDetector;
use WPCommandCenterAI\Master\Admin\AdminPage;
use WPCommandCenterAI\Master\Client\ClientRepository;
use WPCommandCenterAI\Master\Rest\HeartbeatController;
use WPCommandCenterAI\Master\Rest\RegistrationController;
use WPCommandCenterAI\Master\Security\ChallengeStore;
use WPCommandCenterAI\Master\Security\KeyStore;
use WPCommandCenterAI\Master\Security\RequestAuthenticator;

defined( 'ABSPATH' ) || exit;

final class MasterModule implements LifecycleModuleInterface {
	public function id(): string {
		return 'master';
	}

	public function register( Container $container ): void {
		$container->singleton( ClientStatusDetector::class, ClientStatusDetector::class );
		$container->singleton(
			ClientRepository::class,
			static fn ( Container $container ): ClientRepository => new ClientRepository(
				$container->get( ClientStatusDetector::class )
			)
		);
		$container->singleton( ChallengeStore::class, ChallengeStore::class );
		$container->singleton( KeyStore::class, KeyStore::class );
		$container->singleton(
			RequestAuthenticator::class,
			static fn ( Container $container ): RequestAuthenticator => new RequestAuthenticator(
				$container->get( ClientRepository::class )
			)
		);
		$container->singleton(
			AdminPage::class,
			static fn ( Container $container ): AdminPage => new AdminPage(
				$container->get( ClientRepository::class ),
				$container->get( KeyStore::class )
			)
		);
		$container->singleton(
			RegistrationController::class,
			static fn ( Container $container ): RegistrationController => new RegistrationController(
				$container->get( ChallengeStore::class ),
				$container->get( ClientRepository::class ),
				$container->get( KeyStore::class ),
				$container->get( LoggerInterface::class )
			)
		);
		$container->singleton(
			HeartbeatController::class,
			static fn ( Container $container ): HeartbeatController => new HeartbeatController(
				$container->get( RequestAuthenticator::class ),
				$container->get( ClientRepository::class ),
				$container->get( KeyStore::class ),
				$container->get( LoggerInterface::class )
			)
		);
	}

	public function boot( Container $container ): void {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		$container->get( AdminPage::class )->register();
		$container->get( RestApi::class )->add_provider(
			'master.registration',
			$container->get( RegistrationController::class )
		);
		$container->get( RestApi::class )->add_provider(
			'master.heartbeat',
			$container->get( HeartbeatController::class )
		);

		$container->get( CapabilityRegistry::class )->register(
			new Capability(
				'master.client.register',
				__( 'Register client', 'wp-command-center-ai-master' ),
				__( 'Register clients using challenge-response authentication.', 'wp-command-center-ai-master' )
			)
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
		if ( false === get_option( 'wpccai_master_enrollment_token', false ) ) {
			$legacy_secret = (string) get_option( 'wpccai_master_shared_secret', '' );
			$token         = '' !== $legacy_secret ? $legacy_secret : wp_generate_password( 48, false, false );

			add_option( 'wpccai_master_enrollment_token', $token, '', false );
		}

		$container->get( KeyStore::class )->current();
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
