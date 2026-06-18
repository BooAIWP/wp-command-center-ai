<?php
/**
 * Site capability manifest.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Capability;

final class CapabilityManifest {
	public readonly string $site_id;

	public readonly array $capabilities;

	public readonly int $generated_at;

	public function __construct(
		string $site_id,
		array $capabilities,
		int $generated_at
	) {
		$this->site_id      = $site_id;
		$this->capabilities = self::normalize( $capabilities );
		$this->generated_at = max( 0, $generated_at );
	}

	public static function from_array( array $data, ?string $site_id = null ): self {
		return new self(
			$site_id ?? (string) ( $data['site_id'] ?? '' ),
			self::normalize( (array) ( $data['capabilities'] ?? array() ) ),
			max( 0, (int) ( $data['generated_at'] ?? time() ) )
		);
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

	public function checksum(): string {
		return self::checksum_for( $this->capabilities );
	}

	public function to_array(): array {
		return array(
			'site_id'      => $this->site_id,
			'capabilities' => self::normalize( $this->capabilities ),
			'generated_at' => $this->generated_at,
			'checksum'     => $this->checksum(),
		);
	}

	public static function checksum_for( array $capabilities ): string {
		return hash( 'sha256', (string) json_encode( self::normalize( $capabilities ), JSON_UNESCAPED_SLASHES ) );
	}

	public static function normalize( array $capabilities ): array {
		$normalized = array();

		foreach ( $capabilities as $id => $version ) {
			$id      = (string) preg_replace( '/[^a-z0-9._-]/', '', strtolower( trim( (string) $id ) ) );
			$version = trim( (string) $version );

			if ( '' !== $id && '' !== $version ) {
				$normalized[ $id ] = $version;
			}
		}

		ksort( $normalized );

		return $normalized;
	}
}
