<?php
/**
 * Module loader tests.
 *
 * @package WPCommandCenterAI\Tests
 */

namespace WPCommandCenterAI\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use WPCommandCenterAI\Core\Container\Container;
use WPCommandCenterAI\Core\Event\EventBus;
use WPCommandCenterAI\Core\Module\LifecycleModuleInterface;
use WPCommandCenterAI\Core\Module\ModuleLoader;

final class ModuleLoaderTest extends TestCase {
	public function test_it_runs_module_lifecycle_once_and_in_order(): void {
		$container = new Container();
		$events    = new EventBus();
		$loader    = new ModuleLoader( $container, $events );
		$recorder  = (object) array( 'calls' => array() );
		$module    = new class( $recorder ) implements LifecycleModuleInterface {
			public function __construct( private object $recorder ) {
			}

			public function id(): string {
				return 'test';
			}

			public function register( Container $container ): void {
				$this->recorder->calls[] = 'register';
			}

			public function boot( Container $container ): void {
				$this->recorder->calls[] = 'boot';
			}

			public function activate( Container $container ): void {
				$this->recorder->calls[] = 'activate';
			}

			public function deactivate( Container $container ): void {
				$this->recorder->calls[] = 'deactivate';
			}
		};

		$loader->add( $module );
		$loader->boot();
		$loader->boot();
		$loader->activate();
		$loader->deactivate();

		self::assertSame( array( 'register', 'boot', 'activate', 'deactivate' ), $recorder->calls );
	}
}
