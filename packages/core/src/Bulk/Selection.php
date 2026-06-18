<?php
/**
 * Bulk action selection.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Bulk;

use WPCommandCenterAI\Core\Fleet\FleetQuery;

final class Selection {
	public function __construct(
		public readonly FleetQuery $query,
		public readonly array $excluded_site_ids = array()
	) {
	}
}
