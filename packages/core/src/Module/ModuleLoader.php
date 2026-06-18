<?php
/**
 * Module registration and lifecycle coordinator.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Module;

use InvalidArgumentException;
use WPCommandCenterAI\Core\Container\Container;
use WPCommandCenterAI\Core\Event\EventBus;

final class ModuleLoader {
	/**
	 * @var array<string, ModuleInterface>
	 */
	private array $modules = array();

	private bool $registered = false;

	private bool $booted = false;

	public function __construct(
		private Container $container,
		private EventBus $events
	) {
	}

	public function add( ModuleInterface $module ): void {
		if ( $this->registered ) {
			throw new InvalidArgumentException( 'Modules cannot be added after registration.' );
		}

		if ( isset( $this->modules[ $module->id() ] ) ) {
			throw new InvalidArgumentException( sprintf( 'Module "%s" is already loaded.', $module->id() ) );
		}

		$this->modules[ $module->id() ] = $module;
	}

	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		foreach ( $this->modules as $module ) {
			$module->register( $this->container );
			$this->events->dispatch( 'core.module.registered', $module );
		}

		$this->registered = true;
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->register();

		foreach ( $this->modules as $module ) {
			$module->boot( $this->container );
			$this->events->dispatch( 'core.module.booted', $module );
		}

		$this->booted = true;
	}

	public function activate(): void {
		$this->register();

		foreach ( $this->modules as $module ) {
			if ( $module instanceof LifecycleModuleInterface ) {
				$module->activate( $this->container );
			}
		}
	}

	public function deactivate(): void {
		$this->register();

		foreach ( array_reverse( $this->modules ) as $module ) {
			if ( $module instanceof LifecycleModuleInterface ) {
				$module->deactivate( $this->container );
			}
		}
	}

	/**
	 * @return array<string, ModuleInterface>
	 */
	public function all(): array {
		return $this->modules;
	}
}
