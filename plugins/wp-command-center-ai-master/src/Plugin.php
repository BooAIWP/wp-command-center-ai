<?php
/**
 * Master plugin facade.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master;

use WPCommandCenterAI\Core\Kernel;
use WPCommandCenterAI\Master\Module\MasterModule;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	private static ?Kernel $kernel = null;

	public static function boot(): void {
		self::kernel()->boot();
	}

	public static function activate(): void {
		self::kernel()->activate();
	}

	public static function deactivate(): void {
		self::kernel()->deactivate();
	}

	public static function kernel(): Kernel {
		if ( null === self::$kernel ) {
			self::$kernel = ( new Kernel( 'wpccai-master', WPCCAI_MASTER_VERSION ) )
				->add_module( new MasterModule() );
		}

		return self::$kernel;
	}
}
