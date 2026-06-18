<?php
/**
 * Fleet query criteria.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Fleet;

final class FleetQuery {
	public function __construct(
		public readonly array $site_ids = array(),
		public readonly array $groups = array(),
		public readonly array $tags = array(),
		public readonly array $statuses = array(),
		public readonly array $capabilities = array(),
		public readonly int $limit = 100,
		public readonly int $offset = 0
	) {
	}
}
