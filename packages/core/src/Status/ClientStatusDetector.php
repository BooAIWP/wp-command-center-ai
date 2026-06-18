<?php
/**
 * Client connectivity status detector.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Status;

final class ClientStatusDetector {
	public function __construct(
		private int $online_window = 900,
		private int $offline_window = 3900
	) {
	}

	public function detect( ?int $last_seen_at, ?int $now = null ): string {
		$now ??= time();

		if ( null === $last_seen_at || 0 === $last_seen_at || $now - $last_seen_at >= $this->offline_window ) {
			return 'offline';
		}

		if ( $now - $last_seen_at > $this->online_window ) {
			return 'stale';
		}

		return 'online';
	}
}
