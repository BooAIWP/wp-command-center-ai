<?php
/**
 * Service container.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Container;

use Closure;
use Throwable;

final class Container {
	/**
	 * @var array<string, callable|object|string>
	 */
	private array $definitions = array();

	/**
	 * @var array<string, object>
	 */
	private array $instances = array();

	/**
	 * @var array<string, bool>
	 */
	private array $resolving = array();

	public function set( string $id, callable|object|string $definition ): void {
		$this->definitions[ $id ] = $definition;
		unset( $this->instances[ $id ] );
	}

	public function singleton( string $id, callable|object|string $definition ): void {
		$this->set(
			$id,
			static function ( Container $container ) use ( $definition, $id ): object {
				if ( is_object( $definition ) && ! $definition instanceof Closure ) {
					return $definition;
				}

				$service = is_callable( $definition ) ? $definition( $container ) : new $definition();

				if ( ! is_object( $service ) ) {
					throw new ContainerException( sprintf( 'Service "%s" must resolve to an object.', $id ) );
				}

				return $service;
			}
		);
	}

	public function instance( string $id, object $service ): void {
		$this->instances[ $id ] = $service;
		unset( $this->definitions[ $id ] );
	}

	public function alias( string $alias, string $id ): void {
		$this->set( $alias, $id );
	}

	public function has( string $id ): bool {
		return isset( $this->instances[ $id ] ) || isset( $this->definitions[ $id ] );
	}

	public function get( string $id ): object {
		if ( isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		if ( ! isset( $this->definitions[ $id ] ) ) {
			throw new NotFoundException( sprintf( 'Service "%s" is not registered.', $id ) );
		}

		if ( isset( $this->resolving[ $id ] ) ) {
			throw new ContainerException( sprintf( 'Circular dependency detected while resolving "%s".', $id ) );
		}

		$this->resolving[ $id ] = true;

		try {
			$definition = $this->definitions[ $id ];

			if ( is_string( $definition ) && $this->has( $definition ) ) {
				$service = $this->get( $definition );
			} elseif ( is_callable( $definition ) ) {
				$service = $definition( $this );
			} elseif ( is_string( $definition ) && class_exists( $definition ) ) {
				$service = new $definition();
			} else {
				$service = $definition;
			}
		} catch ( Throwable $exception ) {
			throw new ContainerException(
				sprintf( 'Service "%s" could not be resolved: %s', $id, $exception->getMessage() ),
				0,
				$exception
			);
		} finally {
			unset( $this->resolving[ $id ] );
		}

		if ( ! is_object( $service ) ) {
			throw new ContainerException( sprintf( 'Service "%s" must resolve to an object.', $id ) );
		}

		$this->instances[ $id ] = $service;

		return $service;
	}
}
