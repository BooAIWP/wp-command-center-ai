<?php
/**
 * Fleet site value object.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Fleet;

final class FleetSite {
	public function __construct(
		public readonly string $site_id,
		public readonly string $name,
		public readonly string $url,
		public readonly array $metadata = array(),
		public readonly array $groups = array(),
		public readonly array $tags = array(),
		public readonly ?int $last_seen_at = null
	) {
	}
}
