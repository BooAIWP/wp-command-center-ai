<?php
/**
 * Runtime module contract.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Module;

use WPCommandCenterAI\Core\Container\Container;

interface ModuleInterface {
	public function id(): string;

	public function register( Container $container ): void;

	public function boot( Container $container ): void;
}
