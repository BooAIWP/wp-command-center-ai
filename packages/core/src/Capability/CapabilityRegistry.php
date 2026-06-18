<?php
/**
 * Runtime capability registry.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Capability;

use InvalidArgumentException;

final class CapabilityRegistry {
	/**
	 * @var array<string, Capability>
	 */
	private array $capabilities = array();

	public function register( Capability $capability ): void {
		if ( '' === trim( $capability->id ) ) {
			throw new InvalidArgumentException( 'Capability ID cannot be empty.' );
		}

		if ( isset( $this->capabilities[ $capability->id ] ) ) {
			throw new InvalidArgumentException( sprintf( 'Capability "%s" is already registered.', $capability->id ) );
		}

		$this->capabilities[ $capability->id ] = $capability;
	}

	public function has( string $id ): bool {
		return isset( $this->capabilities[ $id ] );
	}

	public function get( string $id ): ?Capability {
		return $this->capabilities[ $id ] ?? null;
	}

	/**
	 * @return array<string, Capability>
	 */
	public function all(): array {
		return $this->capabilities;
	}
}
