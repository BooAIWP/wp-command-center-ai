<?php
/**
 * WordPress-aware logger.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Logging;

final class WordPressLogger extends AbstractLogger {
	private const REDACTED_KEYS = array(
		'authorization',
		'password',
		'secret',
		'token',
	);

	public function __construct( private string $channel ) {
	}

	public function log( string $level, string $message, array $context = array() ): void {
		$this->assert_level( $level );

		$record = array(
			'timestamp' => gmdate( 'c' ),
			'channel'   => $this->channel,
			'level'     => $level,
			'message'   => $this->interpolate( $message, $context ),
			'context'   => $this->redact( $context ),
		);

		if ( function_exists( 'do_action' ) ) {
			do_action( 'wpccai_log', $record );
		}

		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( wp_json_encode( $record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	private function interpolate( string $message, array $context ): string {
		$replace = array();

		foreach ( $context as $key => $value ) {
			if ( is_scalar( $value ) || null === $value ) {
				$replace[ '{' . $key . '}' ] = (string) $value;
			}
		}

		return strtr( $message, $replace );
	}

	private function redact( array $context ): array {
		foreach ( $context as $key => $value ) {
			if ( $this->is_sensitive_key( (string) $key ) ) {
				$context[ $key ] = '[REDACTED]';
			} elseif ( is_array( $value ) ) {
				$context[ $key ] = $this->redact( $value );
			} elseif ( is_object( $value ) ) {
				$context[ $key ] = $value::class;
			}
		}

		return $context;
	}

	private function is_sensitive_key( string $key ): bool {
		$key = strtolower( $key );

		foreach ( self::REDACTED_KEYS as $sensitive_key ) {
			if ( str_contains( $key, $sensitive_key ) ) {
				return true;
			}
		}

		return false;
	}
}
