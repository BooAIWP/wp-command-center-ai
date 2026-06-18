<?php
/**
 * Client status detector tests.
 *
 * @package WPCommandCenterAI\Tests
 */

namespace WPCommandCenterAI\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use WPCommandCenterAI\Core\Status\ClientStatusDetector;

final class ClientStatusDetectorTest extends TestCase {
	public function test_it_detects_online_stale_and_offline_clients(): void {
		$detector = new ClientStatusDetector( 10, 30 );

		self::assertSame( 'offline', $detector->detect( null, 100 ) );
		self::assertSame( 'online', $detector->detect( 91, 100 ) );
		self::assertSame( 'stale', $detector->detect( 89, 100 ) );
		self::assertSame( 'offline', $detector->detect( 70, 100 ) );
	}
}
