<?php
/**
 * Event bus tests.
 *
 * @package WPCommandCenterAI\Tests
 */

namespace WPCommandCenterAI\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use WPCommandCenterAI\Core\Event\EventBus;

final class EventBusTest extends TestCase {
	public function test_it_dispatches_listeners_in_priority_order(): void {
		$events = new EventBus();
		$calls  = array();

		$events->listen(
			'test.event',
			static function () use ( &$calls ): void {
				$calls[] = 'late';
			},
			20
		);
		$events->listen(
			'test.event',
			static function ( string $payload ) use ( &$calls ): void {
				$calls[] = $payload;
			},
			5
		);

		$events->dispatch( 'test.event', 'early' );

		self::assertSame( array( 'early', 'late' ), $calls );
	}

	public function test_it_dispatches_event_objects(): void {
		$events = new EventBus();
		$event  = new \stdClass();
		$seen   = null;

		$events->listen(
			\stdClass::class,
			static function ( object $received ) use ( &$seen ): void {
				$seen = $received;
			}
		);
		$events->dispatch( $event );

		self::assertSame( $event, $seen );
	}
}
