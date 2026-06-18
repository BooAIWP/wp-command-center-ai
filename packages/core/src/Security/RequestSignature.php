<?php
/**
 * Signed request metadata.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Security;

final class RequestSignature {
	public function __construct(
		public readonly string $key_id,
		public readonly int $timestamp,
		public readonly string $nonce,
		public readonly string $signature
	) {
	}

	public static function create(
		string $method,
		string $route,
		string $body,
		KeyPair $key_pair,
		?int $timestamp = null,
		?string $nonce = null
	): self {
		$timestamp ??= time();
		$nonce     ??= Base64Url::encode( random_bytes( 24 ) );
		$canonical = CanonicalRequest::build( $method, $route, $timestamp, $nonce, $body );

		return new self(
			$key_pair->key_id,
			$timestamp,
			$nonce,
			Ed25519::sign( $canonical, $key_pair->private_key )
		);
	}

	public function verify(
		string $method,
		string $route,
		string $body,
		string $public_key
	): bool {
		return Ed25519::verify(
			CanonicalRequest::build( $method, $route, $this->timestamp, $this->nonce, $body ),
			$this->signature,
			$public_key
		);
	}
}
