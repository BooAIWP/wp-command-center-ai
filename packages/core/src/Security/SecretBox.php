<?php
/**
 * Authenticated secret encryption.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Security;

final class SecretBox {
	public static function encrypt( string $plaintext, string $encryption_key ): string {
		self::assert_key( $encryption_key );

		$nonce      = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = sodium_crypto_secretbox( $plaintext, $nonce, $encryption_key );

		return Base64Url::encode( $nonce . $ciphertext );
	}

	public static function decrypt( string $encoded, string $encryption_key ): string {
		self::assert_key( $encryption_key );

		$payload = Base64Url::decode( $encoded );

		if ( strlen( $payload ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			throw new CryptoException( 'Encrypted secret payload is invalid.' );
		}

		$nonce   = substr( $payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$cipher  = substr( $payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$plain   = sodium_crypto_secretbox_open( $cipher, $nonce, $encryption_key );

		if ( false === $plain ) {
			throw new CryptoException( 'Encrypted secret authentication failed.' );
		}

		return $plain;
	}

	public static function derive_key( string $material ): string {
		return sodium_crypto_generichash( $material, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
	}

	private static function assert_key( string $key ): void {
		if ( ! Ed25519::available() || SODIUM_CRYPTO_SECRETBOX_KEYBYTES !== strlen( $key ) ) {
			throw new CryptoException( 'Invalid secret encryption key.' );
		}
	}

	private function __construct() {
	}
}
