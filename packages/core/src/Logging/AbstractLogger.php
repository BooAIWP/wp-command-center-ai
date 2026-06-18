<?php
/**
 * Base logger implementation.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Logging;

use InvalidArgumentException;

abstract class AbstractLogger implements LoggerInterface {
	public function emergency( string $message, array $context = array() ): void {
		$this->log( LogLevel::EMERGENCY, $message, $context );
	}

	public function alert( string $message, array $context = array() ): void {
		$this->log( LogLevel::ALERT, $message, $context );
	}

	public function critical( string $message, array $context = array() ): void {
		$this->log( LogLevel::CRITICAL, $message, $context );
	}

	public function error( string $message, array $context = array() ): void {
		$this->log( LogLevel::ERROR, $message, $context );
	}

	public function warning( string $message, array $context = array() ): void {
		$this->log( LogLevel::WARNING, $message, $context );
	}

	public function notice( string $message, array $context = array() ): void {
		$this->log( LogLevel::NOTICE, $message, $context );
	}

	public function info( string $message, array $context = array() ): void {
		$this->log( LogLevel::INFO, $message, $context );
	}

	public function debug( string $message, array $context = array() ): void {
		$this->log( LogLevel::DEBUG, $message, $context );
	}

	protected function assert_level( string $level ): void {
		if ( ! in_array( $level, LogLevel::ALL, true ) ) {
			throw new InvalidArgumentException( sprintf( 'Unknown log level "%s".', $level ) );
		}
	}
}
