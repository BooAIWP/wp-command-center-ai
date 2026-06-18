<?php
/**
 * Trusted Master public key storage.
 *
 * @package WPCommandCenterAI\Client
 */

namespace WPCommandCenterAI\Client\Security;

use WPCommandCenterAI\Core\Security\CryptoException;
use WPCommandCenterAI\Core\Security\Ed25519;

defined( 'ABSPATH' ) || exit;

final class MasterKeyStore {
	private const OPTION_NAME = 'wpccai_client_master_keys';

	public function trust( string $key_id, string $public_key ): void {
		if ( ! hash_equals( $key_id, Ed25519::key_id_from_public_key( $public_key ) ) ) {
			throw new CryptoException( 'Master key ID does not match the public key.' );
		}

		$keys            = $this->all();
		$keys[ $key_id ] = $public_key;

		update_option( self::OPTION_NAME, $keys, false );
	}

	public function find( string $key_id ): ?string {
		$keys = $this->all();

		return isset( $keys[ $key_id ] ) ? (string) $keys[ $key_id ] : null;
	}

	public function all(): array {
		$keys = get_option( self::OPTION_NAME, array() );

		return is_array( $keys ) ? $keys : array();
	}
}
