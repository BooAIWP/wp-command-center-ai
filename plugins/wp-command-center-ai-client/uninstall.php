<?php
/**
 * Client plugin uninstall handler.
 *
 * @package WPCommandCenterAI\Client
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'wpccai_client_master_url' );
delete_option( 'wpccai_client_shared_secret' );
delete_option( 'wpccai_client_site_id' );
delete_option( 'wpccai_client_last_error' );
delete_option( 'wpccai_client_last_seen' );
delete_option( 'wpccai_client_last_status' );
delete_option( 'wpccai_client_version' );
