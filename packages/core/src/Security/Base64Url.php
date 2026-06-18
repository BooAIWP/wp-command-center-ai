<?php
/**
 * URL-safe Base64 codec.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Security;

final class Base64Url {
	public static function encode( string $value ): string {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}

	public static function decode( string $value ): string {
		$padding = strlen( $value ) % 4;

		if ( 0 !== $padding ) {
			$value .= str_repeat( '=', 4 - $padding );
		}

		$decoded = base64_decode( strtr( $value, '-_', '+/' ), true );

		if ( false === $decoded ) {
			throw new CryptoException( 'Invalid Base64URL value.' );
		}

		return $decoded;
	}

	private function __construct() {
	}
}
