<?php
/**
 * Application kernel.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core;

use LogicException;
use WPCommandCenterAI\Core\Capability\CapabilityRegistry;
use WPCommandCenterAI\Core\Container\Container;
use WPCommandCenterAI\Core\Event\EventBus;
use WPCommandCenterAI\Core\Lifecycle\Activator;
use WPCommandCenterAI\Core\Lifecycle\Deactivator;
use WPCommandCenterAI\Core\Logging\LoggerInterface;
use WPCommandCenterAI\Core\Logging\WordPressLogger;
use WPCommandCenterAI\Core\Module\ModuleInterface;
use WPCommandCenterAI\Core\Module\ModuleLoader;
use WPCommandCenterAI\Core\Rest\RestApi;

final class Kernel {
	private Container $container;

	private ModuleLoader $modules;

	private bool $booted = false;

	public function __construct(
		private string $application_id,
		private string $version,
		?Container $container = null
	) {
		$this->container = $container ?? new Container();
		$this->register_core_services();
		$this->modules = $this->service( ModuleLoader::class );
	}

	public function add_module( ModuleInterface $module ): self {
		if ( $this->booted ) {
			throw new LogicException( 'Modules cannot be added after the kernel has booted.' );
		}

		$this->modules->add( $module );

		return $this;
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->modules->boot();
		$this->service( RestApi::class )->boot();
		$this->service( EventBus::class )->dispatch( 'core.kernel.booted', $this );
		$this->service( LoggerInterface::class )->info(
			'Kernel booted for {application} version {version}.',
			array(
				'application' => $this->application_id,
				'version'     => $this->version,
			)
		);
		$this->booted = true;
	}

	public function activate(): void {
		$this->service( Activator::class )->run();
	}

	public function deactivate(): void {
		$this->service( Deactivator::class )->run();
	}

	public function container(): Container {
		return $this->container;
	}

	/**
	 * @template T of object
	 *
	 * @param class-string<T>|string $id Service identifier.
	 * @return T|object
	 */
	public function service( string $id ): object {
		return $this->container->get( $id );
	}

	private function register_core_services(): void {
		$this->container->instance( Container::class, $this->container );
		$this->container->singleton( EventBus::class, EventBus::class );
		$this->container->singleton( CapabilityRegistry::class, CapabilityRegistry::class );
		$this->container->singleton(
			LoggerInterface::class,
			fn (): LoggerInterface => new WordPressLogger( $this->application_id )
		);
		$this->container->singleton(
			ModuleLoader::class,
			fn ( Container $container ): ModuleLoader => new ModuleLoader(
				$container,
				$container->get( EventBus::class )
			)
		);
		$this->container->singleton(
			RestApi::class,
			fn ( Container $container ): RestApi => new RestApi(
				$container->get( EventBus::class ),
				$container->get( LoggerInterface::class )
			)
		);
		$this->container->singleton(
			Activator::class,
			fn ( Container $container ): Activator => new Activator(
				$container->get( ModuleLoader::class ),
				$container->get( EventBus::class ),
				$container->get( LoggerInterface::class )
			)
		);
		$this->container->singleton(
			Deactivator::class,
			fn ( Container $container ): Deactivator => new Deactivator(
				$container->get( ModuleLoader::class ),
				$container->get( EventBus::class ),
				$container->get( LoggerInterface::class )
			)
		);
	}
}
