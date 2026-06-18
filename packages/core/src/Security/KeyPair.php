<?php
/**
 * Signing key pair.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Security;

final class KeyPair {
	public function __construct(
		public readonly string $key_id,
		public readonly string $public_key,
		public readonly string $private_key,
		public readonly int $created_at
	) {
	}
}
