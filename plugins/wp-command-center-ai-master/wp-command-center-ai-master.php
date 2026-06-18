<?php
/**
 * Plugin Name:       WP Command Center AI - Master
 * Plugin URI:        https://github.com/wp-command-center-ai/wp-command-center-ai
 * Description:       Central command center for connected WordPress sites.
 * Version:           0.3.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            WP Command Center AI
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-command-center-ai-master
 * Domain Path:       /languages
 *
 * @package WPCommandCenterAI\Master
 */

defined( 'ABSPATH' ) || exit;

define( 'WPCCAI_MASTER_VERSION', '0.3.0' );
define( 'WPCCAI_MASTER_FILE', __FILE__ );
define( 'WPCCAI_MASTER_PATH', plugin_dir_path( __FILE__ ) );

$wpccai_master_autoloader = WPCCAI_MASTER_PATH . 'vendor/autoload.php';

if ( file_exists( $wpccai_master_autoloader ) ) {
	require_once $wpccai_master_autoloader;
} else {
	$wpccai_core_bootstrap = dirname( WPCCAI_MASTER_PATH, 2 ) . '/packages/core/bootstrap.php';

	if ( ! file_exists( $wpccai_core_bootstrap ) ) {
		return;
	}

	require_once $wpccai_core_bootstrap;
	require_once WPCCAI_MASTER_PATH . 'src/Plugin.php';
	require_once WPCCAI_MASTER_PATH . 'src/Database/Schema.php';
	require_once WPCCAI_MASTER_PATH . 'src/Database/MigrationManager.php';
	require_once WPCCAI_MASTER_PATH . 'src/Client/ClientRepository.php';
	require_once WPCCAI_MASTER_PATH . 'src/Security/ChallengeStore.php';
	require_once WPCCAI_MASTER_PATH . 'src/Security/KeyStore.php';
	require_once WPCCAI_MASTER_PATH . 'src/Security/RequestAuthenticator.php';
	require_once WPCCAI_MASTER_PATH . 'src/Module/MasterModule.php';
	require_once WPCCAI_MASTER_PATH . 'src/Admin/AdminPage.php';
	require_once WPCCAI_MASTER_PATH . 'src/Rest/RegistrationController.php';
	require_once WPCCAI_MASTER_PATH . 'src/Rest/HeartbeatController.php';
}

register_activation_hook( __FILE__, array( \WPCommandCenterAI\Master\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \WPCommandCenterAI\Master\Plugin::class, 'deactivate' ) );
\WPCommandCenterAI\Master\Plugin::boot();
