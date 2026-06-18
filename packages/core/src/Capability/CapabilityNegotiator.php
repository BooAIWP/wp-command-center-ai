<?php
/**
 * Capability negotiation service.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Capability;

final class CapabilityNegotiator {
	public function negotiate( CapabilityManifest $manifest, array $requirements ): array {
		$result = array();

		foreach ( $requirements as $capability => $minimum_version ) {
			$result[ $capability ] = $manifest->supports(
				(string) $capability,
				null === $minimum_version ? null : (string) $minimum_version
			);
		}

		return $result;
	}
}
