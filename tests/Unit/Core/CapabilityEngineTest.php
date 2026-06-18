<?php
/**
 * Capability Engine tests.
 *
 * @package WPCommandCenterAI\Tests
 */

namespace WPCommandCenterAI\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use WPCommandCenterAI\Core\Capability\Capability;
use WPCommandCenterAI\Core\Capability\CapabilityDiscovery;
use WPCommandCenterAI\Core\Capability\CapabilityManifest;
use WPCommandCenterAI\Core\Capability\CapabilityNegotiator;
use WPCommandCenterAI\Core\Capability\CapabilityRegistry;
use WPCommandCenterAI\Core\Capability\FeatureSet;
use WPCommandCenterAI\Core\Capability\RequirementEvaluator;

final class CapabilityEngineTest extends TestCase {
	public function test_discovery_uses_features_instead_of_platform_versions(): void {
		$registry = new CapabilityRegistry();
		$registry->register(
			new Capability(
				'security.signing',
				'Signing',
				'',
				array(
					'version'  => '2.0.0',
					'requires' => array( 'extensions' => array( 'sodium' ) ),
				)
			)
		);
		$registry->register(
			new Capability(
				'runtime.multisite',
				'Multisite',
				'',
				array(
					'version'  => '1.0.0',
					'requires' => array( 'flags' => array( 'multisite' ) ),
				)
			)
		);

		$manifest = ( new CapabilityDiscovery( $registry, new RequirementEvaluator() ) )->discover(
			'site',
			new FeatureSet( array( 'sodium' ), flags: array( 'multisite' => false ) ),
			100
		);

		self::assertSame( array( 'security.signing' => '2.0.0' ), $manifest->capabilities );
	}

	public function test_manifest_checksum_is_order_independent(): void {
		self::assertSame(
			CapabilityManifest::checksum_for( array( 'b' => '1.0.0', 'a' => '2.0.0' ) ),
			CapabilityManifest::checksum_for( array( 'a' => '2.0.0', 'b' => '1.0.0' ) )
		);
	}

	public function test_feature_flags_are_case_insensitive(): void {
		$features = new FeatureSet( flags: array( 'MultiSite' => true ) );

		self::assertTrue( $features->flag( 'multisite' ) );
	}

	public function test_negotiator_returns_accepted_and_missing_capabilities(): void {
		$manifest   = new CapabilityManifest( 'site', array( 'inventory.snapshot' => '1.2.0' ), 100 );
		$negotiator = new CapabilityNegotiator();
		$policy     = array(
			'inventory.snapshot' => '1.0.0',
			'protocol.heartbeat' => '1.0.0',
		);

		self::assertSame( array( 'inventory.snapshot' => '1.2.0' ), $negotiator->accepted( $manifest, $policy ) );
		self::assertSame( array( 'protocol.heartbeat' ), $negotiator->missing( $manifest, $policy ) );
	}
}
