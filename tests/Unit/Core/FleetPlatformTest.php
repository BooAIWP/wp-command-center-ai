<?php
/**
 * Fleet platform domain tests.
 *
 * @package WPCommandCenterAI\Tests
 */

namespace WPCommandCenterAI\Tests\Unit\Core;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WPCommandCenterAI\Core\Capability\CapabilityManifest;
use WPCommandCenterAI\Core\Capability\CapabilityNegotiator;
use WPCommandCenterAI\Core\Inventory\InventoryNormalizer;
use WPCommandCenterAI\Core\Job\Job;
use WPCommandCenterAI\Core\Job\JobLifecycle;

final class FleetPlatformTest extends TestCase {
	public function test_inventory_normalization_is_deterministic(): void {
		$normalizer = new InventoryNormalizer();
		$first      = $normalizer->normalize(
			'site',
			array(
				'plugins' => array(
					array( 'slug' => 'z-plugin', 'version' => '1.0' ),
					array( 'version' => '2.0', 'slug' => 'a-plugin' ),
				),
			)
		);
		$second     = $normalizer->normalize(
			'site',
			array(
				'plugins' => array(
					array( 'slug' => 'a-plugin', 'version' => '2.0' ),
					array( 'version' => '1.0', 'slug' => 'z-plugin' ),
				),
			)
		);

		self::assertSame( $first->checksum, $second->checksum );
	}

	public function test_capability_negotiation_is_version_independent(): void {
		$manifest   = new CapabilityManifest( 'site', array( 'core.inventory' => '2.1.0' ), 100 );
		$negotiator = new CapabilityNegotiator();

		self::assertSame(
			array(
				'core.inventory' => true,
				'core.jobs'      => false,
			),
			$negotiator->negotiate(
				$manifest,
				array(
					'core.inventory' => '2.0.0',
					'core.jobs'      => null,
				)
			)
		);
	}

	public function test_job_lifecycle_rejects_invalid_transitions(): void {
		$lifecycle = new JobLifecycle();
		$lifecycle->assert_transition( Job::PENDING, Job::DISPATCHED );

		$this->expectException( InvalidArgumentException::class );
		$lifecycle->assert_transition( Job::SUCCEEDED, Job::RUNNING );
	}
}
