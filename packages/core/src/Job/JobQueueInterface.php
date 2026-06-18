<?php
/**
 * Job queue contract.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Job;

interface JobQueueInterface {
	public function enqueue( Job $job ): void;

	public function reserve_for_site( string $site_id, int $limit = 10 ): array;

	public function transition( string $job_id, string $status, array $result = array() ): void;
}
