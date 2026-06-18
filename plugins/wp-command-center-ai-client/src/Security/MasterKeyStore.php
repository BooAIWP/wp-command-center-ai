<?php
/**
 * Trusted Master public key storage.
 *
 * @package WPCommandCenterAI\Client
 */

namespace WPCommandCenterAI\Client\Security;

defined( 'ABSPATH' ) || exit;

final class MasterKeyStore {
	private const OPTION_NAME = 'wpccai_client_master_keys';

	public function trust( string $key_id, string $public_key ): void {
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
