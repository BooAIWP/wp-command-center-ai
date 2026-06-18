<?php
/**
 * Master signing key storage.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Security;

use WPCommandCenterAI\Core\Security\Ed25519;
use WPCommandCenterAI\Core\Security\KeyPair;
use WPCommandCenterAI\Core\Security\RotationPolicy;
use WPCommandCenterAI\Core\Security\SecretBox;

defined( 'ABSPATH' ) || exit;

final class KeyStore {
	private const OPTION_NAME = 'wpccai_master_key_ring';

	public function current(): KeyPair {
		$ring = $this->load();

		if ( empty( $ring['current'] ) ) {
			return $this->generate();
		}

		return $this->hydrate( $ring['current'] );
	}

	public function public_keys(): array {
		$ring = $this->load();
		$keys = array();

		foreach ( array( 'current', 'previous' ) as $slot ) {
			if ( ! empty( $ring[ $slot ]['key_id'] ) && ! empty( $ring[ $slot ]['public_key'] ) ) {
				$keys[ $ring[ $slot ]['key_id'] ] = $ring[ $slot ]['public_key'];
			}
		}

		return $keys;
	}

	public function next(): ?KeyPair {
		$ring = $this->load();

		return empty( $ring['next'] ) ? null : $this->hydrate( $ring['next'] );
	}

	public function prepare_rotation( ?int $now = null ): ?KeyPair {
		if ( ! $this->rotation_due( $now ) ) {
			return $this->next();
		}

		if ( null !== $this->next() ) {
			return $this->next();
		}

		$ring         = $this->load();
		$next         = Ed25519::generate_key_pair( $now );
		$ring['next'] = $this->serialize( $next );

		update_option( self::OPTION_NAME, $ring, false );

		return $next;
	}

	public function rotation_due( ?int $now = null ): bool {
		return ( new RotationPolicy() )->rotation_due( $this->current()->created_at, $now );
	}

	public function rotate(): KeyPair {
		$ring              = $this->load();
		$ring['previous']  = $ring['current'] ?? null;
		$key_pair          = $this->next() ?? Ed25519::generate_key_pair();
		$ring['current']   = $this->serialize( $key_pair );
		$ring['next']      = null;
		$ring['rotated_at'] = time();

		update_option( self::OPTION_NAME, $ring, false );

		return $key_pair;
	}

	public function generate(): KeyPair {
		$key_pair = Ed25519::generate_key_pair();

		update_option(
			self::OPTION_NAME,
			array(
				'current'  => $this->serialize( $key_pair ),
				'next'     => null,
				'previous' => null,
			),
			false
		);

		return $key_pair;
	}

	private function load(): array {
		$ring = get_option( self::OPTION_NAME, array() );

		return is_array( $ring ) ? $ring : array();
	}

	private function serialize( KeyPair $key_pair ): array {
		return array(
			'key_id'     => $key_pair->key_id,
			'public_key' => $key_pair->public_key,
			'private_key' => SecretBox::encrypt( $key_pair->private_key, $this->encryption_key() ),
			'created_at' => $key_pair->created_at,
		);
	}

	private function hydrate( array $data ): KeyPair {
		return new KeyPair(
			(string) $data['key_id'],
			(string) $data['public_key'],
			SecretBox::decrypt( (string) $data['private_key'], $this->encryption_key() ),
			absint( $data['created_at'] )
		);
	}

	private function encryption_key(): string {
		$material = defined( 'AUTH_KEY' ) ? AUTH_KEY : wp_salt( 'auth' );

		return SecretBox::derive_key( 'wpccai-master:' . $material );
	}
}
