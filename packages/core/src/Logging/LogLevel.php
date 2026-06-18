<?php
/**
 * Log levels.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Logging;

final class LogLevel {
	public const EMERGENCY = 'emergency';
	public const ALERT     = 'alert';
	public const CRITICAL  = 'critical';
	public const ERROR     = 'error';
	public const WARNING   = 'warning';
	public const NOTICE    = 'notice';
	public const INFO      = 'info';
	public const DEBUG     = 'debug';

	public const ALL = array(
		self::EMERGENCY,
		self::ALERT,
		self::CRITICAL,
		self::ERROR,
		self::WARNING,
		self::NOTICE,
		self::INFO,
		self::DEBUG,
	);

	private function __construct() {
	}
}
