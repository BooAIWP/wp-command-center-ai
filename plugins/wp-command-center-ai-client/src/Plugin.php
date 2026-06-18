<?php
/**
 * Client plugin facade.
 *
 * @package WPCommandCenterAI\Client
 */

namespace WPCommandCenterAI\Client;

use WPCommandCenterAI\Client\Module\ClientModule;
use WPCommandCenterAI\Core\Kernel;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	public const CRON_HOOK = 'wpccai_client_heartbeat';

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
			self::$kernel = ( new Kernel( 'wpccai-client', WPCCAI_CLIENT_VERSION ) )
				->add_module( new ClientModule() );
		}

		return self::$kernel;
	}
}
