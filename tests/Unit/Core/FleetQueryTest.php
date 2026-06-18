<?php
/**
 * Fleet query tests.
 *
 * @package WPCommandCenterAI\Tests
 */

namespace WPCommandCenterAI\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use WPCommandCenterAI\Core\Fleet\FleetQuery;

final class FleetQueryTest extends TestCase {
	public function test_it_carries_scalable_selection_criteria(): void {
		$query = new FleetQuery(
			site_ids: array( 'site-1' ),
			groups: array( 'production' ),
			tags: array( 'commerce' ),
			statuses: array( 'online' ),
			capabilities: array( 'core.inventory' ),
			limit: 500,
			offset: 100
		);

		self::assertSame( array( 'production' ), $query->groups );
		self::assertSame( array( 'commerce' ), $query->tags );
		self::assertSame( 500, $query->limit );
		self::assertSame( 100, $query->offset );
	}
}
