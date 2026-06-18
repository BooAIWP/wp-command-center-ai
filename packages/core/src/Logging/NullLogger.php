<?php
/**
 * No-operation logger.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Logging;

final class NullLogger extends AbstractLogger {
	public function log( string $level, string $message, array $context = array() ): void {
		$this->assert_level( $level );
	}
}
