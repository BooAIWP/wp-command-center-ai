<?php
/**
 * Canonical signed request representation.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Security;

final class CanonicalRequest {
	public static function build(
		string $method,
		string $route,
		int $timestamp,
		string $nonce,
		string $body
	): string {
		return implode(
			"\n",
			array(
				strtoupper( trim( $method ) ),
				'/' . ltrim( trim( $route ), '/' ),
				(string) $timestamp,
				$nonce,
				hash( 'sha256', $body ),
			)
		);
	}

	private function __construct() {
	}
}
