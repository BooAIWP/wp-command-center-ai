<?php
/**
 * Runtime feature set.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Capability;

final class FeatureSet {
	public function __construct(
		private array $extensions = array(),
		private array $functions = array(),
		private array $classes = array(),
		private array $constants = array(),
		private array $flags = array()
	) {
		$this->extensions = $this->normalize( $this->extensions );
		$this->functions  = $this->normalize( $this->functions );
		$this->classes    = $this->normalize( $this->classes );
		$this->constants  = $this->normalize( $this->constants );
		$normalized_flags = array();

		foreach ( $this->flags as $flag => $enabled ) {
			$normalized_flags[ strtolower( (string) $flag ) ] = (bool) $enabled;
		}

		$this->flags = $normalized_flags;
	}

	public function has_extension( string $extension ): bool {
		return isset( $this->extensions[ strtolower( $extension ) ] );
	}

	public function has_function( string $function ): bool {
		return isset( $this->functions[ strtolower( $function ) ] );
	}

	public function has_class( string $class ): bool {
		return isset( $this->classes[ strtolower( $class ) ] );
	}

	public function has_constant( string $constant ): bool {
		return isset( $this->constants[ strtolower( $constant ) ] );
	}

	public function flag( string $flag ): bool {
		return ! empty( $this->flags[ strtolower( $flag ) ] );
	}

	private function normalize( array $values ): array {
		$normalized = array();

		foreach ( $values as $value ) {
			$normalized[ strtolower( (string) $value ) ] = true;
		}

		return $normalized;
	}
}
