<?php
/**
 * Activation coordinator.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Lifecycle;

use WPCommandCenterAI\Core\Event\EventBus;
use WPCommandCenterAI\Core\Logging\LoggerInterface;
use WPCommandCenterAI\Core\Module\ModuleLoader;

final class Activator {
	public function __construct(
		private ModuleLoader $modules,
		private EventBus $events,
		private LoggerInterface $logger
	) {
	}

	public function run(): void {
		$this->events->dispatch( 'core.activation.starting' );
		$this->modules->activate();
		$this->events->dispatch( 'core.activation.completed' );
		$this->logger->info( 'Plugin activation completed.' );
	}
}
