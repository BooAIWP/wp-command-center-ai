<?php
/**
 * REST API bootstrap.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Rest;

use InvalidArgumentException;
use WPCommandCenterAI\Core\Event\EventBus;
use WPCommandCenterAI\Core\Logging\LoggerInterface;

final class RestApi {
	/**
	 * @var array<string, RestRouteProviderInterface>
	 */
	private array $providers = array();

	private bool $hooked = false;

	private bool $registered = false;

	public function __construct(
		private EventBus $events,
		private LoggerInterface $logger
	) {
	}

	public function add_provider( string $id, RestRouteProviderInterface $provider ): void {
		if ( $this->registered ) {
			throw new InvalidArgumentException( 'REST providers cannot be added after route registration.' );
		}

		if ( isset( $this->providers[ $id ] ) ) {
			throw new InvalidArgumentException( sprintf( 'REST provider "%s" is already registered.', $id ) );
		}

		$this->providers[ $id ] = $provider;
	}

	public function boot(): void {
		if ( $this->hooked ) {
			return;
		}

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		$this->hooked = true;
	}

	public function register_routes(): void {
		if ( $this->registered ) {
			return;
		}

		foreach ( $this->providers as $id => $provider ) {
			$provider->register_routes();
			$this->logger->debug( 'REST route provider registered: {provider}.', array( 'provider' => $id ) );
			$this->events->dispatch( 'core.rest.provider_registered', $provider );
		}

		$this->registered = true;
	}
}
