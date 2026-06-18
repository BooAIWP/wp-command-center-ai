<?php
/**
 * Negotiated capability persistence.
 *
 * @package WPCommandCenterAI\Client
 */

namespace WPCommandCenterAI\Client\Capability;

use WPCommandCenterAI\Core\Capability\CapabilityManifest;

defined( 'ABSPATH' ) || exit;

final class NegotiatedCapabilityStore {
	private const OPTION_NAME = 'wpccai_client_negotiated_capabilities';

	public function store( array $capabilities, string $checksum ): bool {
		$capabilities = CapabilityManifest::normalize( $capabilities );

		if ( '' === $checksum || ! hash_equals( CapabilityManifest::checksum_for( $capabilities ), $checksum ) ) {
			return false;
		}

		update_option(
			self::OPTION_NAME,
			array(
				'capabilities' => $capabilities,
				'checksum'     => $checksum,
				'updated_at'   => time(),
			),
			false
		);

		return true;
	}

	public function all(): array {
		$stored = get_option( self::OPTION_NAME, array() );

		return is_array( $stored ) ? $stored : array();
	}
}
