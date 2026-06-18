<?php
/**
 * REST API bootstrap tests.
 *
 * @package WPCommandCenterAI\Tests
 */

namespace WPCommandCenterAI\Tests\Unit\Core;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WPCommandCenterAI\Core\Event\EventBus;
use WPCommandCenterAI\Core\Logging\NullLogger;
use WPCommandCenterAI\Core\Rest\RestApi;
use WPCommandCenterAI\Core\Rest\RestRouteProviderInterface;

final class RestApiTest extends TestCase {
	public function test_it_registers_each_provider_once(): void {
		$rest_api = new RestApi( new EventBus(), new NullLogger() );
		$provider = new class() implements RestRouteProviderInterface {
			public int $calls = 0;

			public function register_routes(): void {
				++$this->calls;
			}
		};

		$rest_api->add_provider( 'test', $provider );
		$rest_api->register_routes();
		$rest_api->register_routes();

		self::assertSame( 1, $provider->calls );
	}

	public function test_it_rejects_duplicate_provider_ids(): void {
		$rest_api = new RestApi( new EventBus(), new NullLogger() );
		$provider = new class() implements RestRouteProviderInterface {
			public function register_routes(): void {
			}
		};

		$rest_api->add_provider( 'test', $provider );
		$this->expectException( InvalidArgumentException::class );
		$rest_api->add_provider( 'test', $provider );
	}
}
