<?php
/**
 * Site capability manifest.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Capability;

final class CapabilityManifest {
	public function __construct(
		public readonly string $site_id,
		public readonly array $capabilities,
		public readonly int $generated_at
	) {
	}

	public function supports( string $capability, ?string $minimum_version = null ): bool {
		if ( ! isset( $this->capabilities[ $capability ] ) ) {
			return false;
		}

		if ( null === $minimum_version ) {
			return true;
		}

		return version_compare( (string) $this->capabilities[ $capability ], $minimum_version, '>=' );
	}
}
