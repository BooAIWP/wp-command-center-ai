<?php
/**
 * Fleet job value object.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Job;

final class Job {
	public const PENDING   = 'pending';
	public const DISPATCHED = 'dispatched';
	public const RUNNING   = 'running';
	public const SUCCEEDED = 'succeeded';
	public const FAILED    = 'failed';
	public const RETRYING  = 'retrying';
	public const CANCELLED = 'cancelled';

	public function __construct(
		public readonly string $job_id,
		public readonly string $site_id,
		public readonly string $type,
		public readonly array $payload,
		public readonly string $status = self::PENDING,
		public readonly int $attempts = 0,
		public readonly int $max_attempts = 3,
		public readonly ?int $available_at = null,
		public readonly ?string $batch_id = null
	) {
	}
}
