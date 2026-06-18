<?php
/**
 * Deactivation coordinator.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Lifecycle;

use WPCommandCenterAI\Core\Event\EventBus;
use WPCommandCenterAI\Core\Logging\LoggerInterface;
use WPCommandCenterAI\Core\Module\ModuleLoader;

final class Deactivator {
	public function __construct(
		private ModuleLoader $modules,
		private EventBus $events,
		private LoggerInterface $logger
	) {
	}

	public function run(): void {
		$this->events->dispatch( 'core.deactivation.starting' );
		$this->modules->deactivate();
		$this->events->dispatch( 'core.deactivation.completed' );
		$this->logger->info( 'Plugin deactivation completed.' );
	}
}
