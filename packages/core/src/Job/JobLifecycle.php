<?php
/**
 * Job lifecycle state machine.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Job;

use InvalidArgumentException;

final class JobLifecycle {
	private const TRANSITIONS = array(
		Job::PENDING    => array( Job::DISPATCHED, Job::CANCELLED ),
		Job::DISPATCHED => array( Job::RUNNING, Job::FAILED, Job::RETRYING ),
		Job::RUNNING    => array( Job::SUCCEEDED, Job::FAILED, Job::RETRYING ),
		Job::RETRYING   => array( Job::DISPATCHED, Job::CANCELLED ),
		Job::FAILED     => array(),
		Job::SUCCEEDED  => array(),
		Job::CANCELLED  => array(),
	);

	public function assert_transition( string $from, string $to ): void {
		if ( ! in_array( $to, self::TRANSITIONS[ $from ] ?? array(), true ) ) {
			throw new InvalidArgumentException( sprintf( 'Invalid job transition from "%s" to "%s".', $from, $to ) );
		}
	}
}
