<?php
/**
 * Registration challenge persistence.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Security;

use WPCommandCenterAI\Core\Security\Base64Url;

defined( 'ABSPATH' ) || exit;

final class ChallengeStore {
	private const LIFETIME = 300;

	public function issue( array $registration ): array {
		$challenge_id = wp_generate_uuid4();
		$challenge    = Base64Url::encode( random_bytes( 32 ) );
		$record       = array(
			'challenge'   => $challenge,
			'site_name'   => sanitize_text_field( (string) $registration['site_name'] ),
			'site_url'    => esc_url_raw( (string) $registration['site_url'] ),
			'key_id'      => sanitize_text_field( (string) $registration['key_id'] ),
			'public_key'  => sanitize_text_field( (string) $registration['public_key'] ),
			'expires_at'  => time() + self::LIFETIME,
		);

		set_transient( $this->key( $challenge_id ), $record, self::LIFETIME );

		return array(
			'challenge_id' => $challenge_id,
			'challenge'    => $challenge,
			'expires_at'   => $record['expires_at'],
		);
	}

	public function consume( string $challenge_id ): ?array {
		$key    = $this->key( $challenge_id );
		$record = get_transient( $key );

		delete_transient( $key );

		if ( ! is_array( $record ) || absint( $record['expires_at'] ?? 0 ) < time() ) {
			return null;
		}

		return $record;
	}

	private function key( string $challenge_id ): string {
		return 'wpccai_challenge_' . md5( $challenge_id );
	}
}
