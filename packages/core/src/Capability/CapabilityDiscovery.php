<?php
/**
 * Capability discovery service.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Capability;

final class CapabilityDiscovery {
	public function __construct(
		private CapabilityRegistry $registry,
		private RequirementEvaluator $requirements
	) {
	}

	public function discover( string $site_id, FeatureSet $features, ?int $generated_at = null ): CapabilityManifest {
		$capabilities = array();

		foreach ( $this->registry->all() as $capability ) {
			if ( ! $this->requirements->supports( $capability, $features ) ) {
				continue;
			}

			$capabilities[ $capability->id ] = (string) ( $capability->metadata['version'] ?? '1.0.0' );
		}

		return new CapabilityManifest( $site_id, $capabilities, $generated_at ?? time() );
	}
}
