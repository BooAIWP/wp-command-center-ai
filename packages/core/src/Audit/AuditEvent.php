<?php
/**
 * Immutable audit event.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Audit;

final class AuditEvent {
	public function __construct(
		public readonly string $event_id,
		public readonly string $event_type,
		public readonly string $actor_type,
		public readonly string $actor_id,
		public readonly ?string $site_id,
		public readonly ?string $job_id,
		public readonly array $context,
		public readonly int $occurred_at
	) {
	}
}
