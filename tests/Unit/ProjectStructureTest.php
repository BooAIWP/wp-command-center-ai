<?php
/**
 * Project structure tests.
 *
 * @package WPCommandCenterAI\Tests
 */

namespace WPCommandCenterAI\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProjectStructureTest extends TestCase {
	/**
	 * @dataProvider pluginFilesProvider
	 */
	public function test_required_plugin_files_exist( string $path ): void {
		self::assertFileExists( dirname( __DIR__, 2 ) . '/' . $path );
	}

	public static function pluginFilesProvider(): array {
		return array(
			'master bootstrap' => array( 'plugins/wp-command-center-ai-master/wp-command-center-ai-master.php' ),
			'client bootstrap' => array( 'plugins/wp-command-center-ai-client/wp-command-center-ai-client.php' ),
			'master readme'    => array( 'plugins/wp-command-center-ai-master/readme.txt' ),
			'client readme'    => array( 'plugins/wp-command-center-ai-client/readme.txt' ),
		);
	}
}
