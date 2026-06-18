<?php
/**
 * Inventory normalization service.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Inventory;

final class InventoryNormalizer {
	public function normalize( string $site_id, array $payload ): InventorySnapshot {
		$environment = $this->sort_recursive( (array) ( $payload['environment'] ?? array() ) );
		$wordpress   = $this->sort_recursive( (array) ( $payload['wordpress'] ?? array() ) );
		$plugins     = $this->normalize_components( (array) ( $payload['plugins'] ?? array() ) );
		$themes      = $this->normalize_components( (array) ( $payload['themes'] ?? array() ) );
		$canonical   = array(
			'environment' => $environment,
			'wordpress'   => $wordpress,
			'plugins'     => $plugins,
			'themes'      => $themes,
		);

		return new InventorySnapshot(
			$site_id,
			hash( 'sha256', (string) json_encode( $canonical, JSON_UNESCAPED_SLASHES ) ),
			absint( $payload['collected_at'] ?? time() ),
			$environment,
			$wordpress,
			$plugins,
			$themes
		);
	}

	private function normalize_components( array $components ): array {
		$normalized = array();

		foreach ( $components as $component ) {
			if ( ! is_array( $component ) || empty( $component['slug'] ) ) {
				continue;
			}

			$slug                = strtolower( trim( (string) $component['slug'] ) );
			$normalized[ $slug ] = $this->sort_recursive( $component );
		}

		ksort( $normalized );

		return array_values( $normalized );
	}

	private function sort_recursive( array $value ): array {
		foreach ( $value as $key => $item ) {
			if ( is_array( $item ) ) {
				$value[ $key ] = $this->sort_recursive( $item );
			}
		}

		if ( ! array_is_list( $value ) ) {
			ksort( $value );
		}

		return $value;
	}
}
