<?php
/**
 * Signing key rotation policy.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Security;

final class RotationPolicy {
	public const DEFAULT_INTERVAL = 7776000;
	public const DEFAULT_GRACE    = 604800;

	public function __construct(
		private int $rotation_interval = self::DEFAULT_INTERVAL,
		private int $grace_period = self::DEFAULT_GRACE
	) {
	}

	public function rotation_due( int $created_at, ?int $now = null ): bool {
		return ( $now ?? time() ) >= $created_at + $this->rotation_interval;
	}

	public function grace_expired( int $retired_at, ?int $now = null ): bool {
		return ( $now ?? time() ) >= $retired_at + $this->grace_period;
	}
}
