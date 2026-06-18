<?php
/**
 * Plugin Name:       WP Command Center AI - Client
 * Plugin URI:        https://github.com/wp-command-center-ai/wp-command-center-ai
 * Description:       Secure connector for sites managed by WP Command Center AI.
 * Version:           0.3.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            WP Command Center AI
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-command-center-ai-client
 * Domain Path:       /languages
 *
 * @package WPCommandCenterAI\Client
 */

defined( 'ABSPATH' ) || exit;

define( 'WPCCAI_CLIENT_VERSION', '0.3.0' );
define( 'WPCCAI_CLIENT_FILE', __FILE__ );
define( 'WPCCAI_CLIENT_PATH', plugin_dir_path( __FILE__ ) );

$wpccai_client_autoloader = WPCCAI_CLIENT_PATH . 'vendor/autoload.php';

if ( file_exists( $wpccai_client_autoloader ) ) {
	require_once $wpccai_client_autoloader;
} else {
	$wpccai_core_bootstrap = dirname( WPCCAI_CLIENT_PATH, 2 ) . '/packages/core/bootstrap.php';

	if ( ! file_exists( $wpccai_core_bootstrap ) ) {
		return;
	}

	require_once $wpccai_core_bootstrap;
	require_once WPCCAI_CLIENT_PATH . 'src/Plugin.php';
	require_once WPCCAI_CLIENT_PATH . 'src/Security/KeyStore.php';
	require_once WPCCAI_CLIENT_PATH . 'src/Security/MasterKeyStore.php';
	require_once WPCCAI_CLIENT_PATH . 'src/Service/Registration.php';
	require_once WPCCAI_CLIENT_PATH . 'src/Module/ClientModule.php';
	require_once WPCCAI_CLIENT_PATH . 'src/Admin/SettingsPage.php';
	require_once WPCCAI_CLIENT_PATH . 'src/Service/Heartbeat.php';
}

register_activation_hook( __FILE__, array( \WPCommandCenterAI\Client\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \WPCommandCenterAI\Client\Plugin::class, 'deactivate' ) );
\WPCommandCenterAI\Client\Plugin::boot();
