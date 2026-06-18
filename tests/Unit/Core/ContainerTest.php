<?php
/**
 * Service container tests.
 *
 * @package WPCommandCenterAI\Tests
 */

namespace WPCommandCenterAI\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use WPCommandCenterAI\Core\Container\Container;
use WPCommandCenterAI\Core\Container\ContainerException;
use WPCommandCenterAI\Core\Container\NotFoundException;

final class ContainerTest extends TestCase {
	public function test_it_resolves_and_reuses_services(): void {
		$container = new Container();
		$container->singleton( 'service', static fn (): object => new \stdClass() );

		self::assertSame( $container->get( 'service' ), $container->get( 'service' ) );
	}

	public function test_it_resolves_aliases(): void {
		$container = new Container();
		$service   = new \stdClass();

		$container->instance( 'service', $service );
		$container->alias( 'alias', 'service' );

		self::assertSame( $service, $container->get( 'alias' ) );
	}

	public function test_it_rejects_missing_services(): void {
		$this->expectException( NotFoundException::class );

		( new Container() )->get( 'missing' );
	}

	public function test_it_detects_circular_dependencies(): void {
		$container = new Container();
		$container->set( 'first', static fn ( Container $container ): object => $container->get( 'second' ) );
		$container->set( 'second', static fn ( Container $container ): object => $container->get( 'first' ) );

		$this->expectException( ContainerException::class );

		$container->get( 'first' );
	}
}
