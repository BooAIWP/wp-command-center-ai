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

	public function accepted( CapabilityManifest $manifest, array $requirements ): array {
		$accepted = array();

		foreach ( $this->negotiate( $manifest, $requirements ) as $capability => $supported ) {
			if ( $supported ) {
				$accepted[ $capability ] = (string) $manifest->capabilities[ $capability ];
			}
		}

		ksort( $accepted );

		return $accepted;
	}

	public function missing( CapabilityManifest $manifest, array $requirements ): array {
		return array_keys(
			array_filter(
				$this->negotiate( $manifest, $requirements ),
				static fn ( bool $supported ): bool => ! $supported
			)
		);
	}
}
