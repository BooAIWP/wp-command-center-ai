<?php
/**
 * Capability registry tests.
 *
 * @package WPCommandCenterAI\Tests
 */

namespace WPCommandCenterAI\Tests\Unit\Core;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WPCommandCenterAI\Core\Capability\Capability;
use WPCommandCenterAI\Core\Capability\CapabilityRegistry;

final class CapabilityRegistryTest extends TestCase {
	public function test_it_registers_capabilities(): void {
		$registry   = new CapabilityRegistry();
		$capability = new Capability( 'test.read', 'Read test data' );

		$registry->register( $capability );

		self::assertTrue( $registry->has( 'test.read' ) );
		self::assertSame( $capability, $registry->get( 'test.read' ) );
	}

	public function test_it_rejects_duplicate_capabilities(): void {
		$registry = new CapabilityRegistry();
		$registry->register( new Capability( 'test.read', 'Read test data' ) );

		$this->expectException( InvalidArgumentException::class );

		$registry->register( new Capability( 'test.read', 'Duplicate' ) );
	}
}
