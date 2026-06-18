<?php
/**
 * Master plugin uninstall handler.
 *
 * @package WPCommandCenterAI\Master
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'wpccai_master_clients' );
delete_option( 'wpccai_master_shared_secret' );
delete_option( 'wpccai_master_version' );
