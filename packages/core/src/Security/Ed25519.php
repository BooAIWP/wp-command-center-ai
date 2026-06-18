<?php
/**
 * Ed25519 signing primitives.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Security;

final class Ed25519 {
	public static function available(): bool {
		return function_exists( 'sodium_crypto_sign_keypair' );
	}

	public static function generate_key_pair( ?int $created_at = null ): KeyPair {
		self::assert_available();

		$key_pair   = sodium_crypto_sign_keypair();
		$public_key = sodium_crypto_sign_publickey( $key_pair );
		$private_key = sodium_crypto_sign_secretkey( $key_pair );

		return new KeyPair(
			self::key_id( $public_key ),
			Base64Url::encode( $public_key ),
			Base64Url::encode( $private_key ),
			$created_at ?? time()
		);
	}

	public static function sign( string $message, string $private_key ): string {
		self::assert_available();

		$decoded_key = Base64Url::decode( $private_key );

		if ( SODIUM_CRYPTO_SIGN_SECRETKEYBYTES !== strlen( $decoded_key ) ) {
			throw new CryptoException( 'Invalid Ed25519 private key length.' );
		}

		return Base64Url::encode( sodium_crypto_sign_detached( $message, $decoded_key ) );
	}

	public static function verify( string $message, string $signature, string $public_key ): bool {
		self::assert_available();

		try {
			$decoded_signature = Base64Url::decode( $signature );
			$decoded_key       = Base64Url::decode( $public_key );
		} catch ( CryptoException ) {
			return false;
		}

		if (
			SODIUM_CRYPTO_SIGN_BYTES !== strlen( $decoded_signature )
			|| SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES !== strlen( $decoded_key )
		) {
			return false;
		}

		return sodium_crypto_sign_verify_detached( $decoded_signature, $message, $decoded_key );
	}

	public static function key_id( string $binary_public_key ): string {
		return substr( hash( 'sha256', $binary_public_key ), 0, 24 );
	}

	public static function key_id_from_public_key( string $public_key ): string {
		$decoded_key = Base64Url::decode( $public_key );

		if ( SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES !== strlen( $decoded_key ) ) {
			throw new CryptoException( 'Invalid Ed25519 public key length.' );
		}

		return self::key_id( $decoded_key );
	}

	private static function assert_available(): void {
		if ( ! self::available() ) {
			throw new CryptoException( 'The PHP Sodium extension is required.' );
		}
	}

	private function __construct() {
	}
}
