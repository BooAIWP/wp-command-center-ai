<?php
/**
 * Bulk action definition.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Bulk;

final class BulkAction {
	public function __construct(
		public readonly string $action_id,
		public readonly string $job_type,
		public readonly array $payload = array(),
		public readonly array $required_capabilities = array()
	) {
	}
}
