<?php
/**
 * Module lifecycle contract.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Module;

use WPCommandCenterAI\Core\Container\Container;

interface LifecycleModuleInterface extends ModuleInterface {
	public function activate( Container $container ): void;

	public function deactivate( Container $container ): void;
}
