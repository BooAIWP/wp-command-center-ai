<?php
/**
 * Development autoloader for the core package.
 *
 * @package WPCommandCenterAI\Core
 */

spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'WPCommandCenterAI\\Core\\';

		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( $prefix ) );
		$file           = __DIR__ . '/src/' . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);
