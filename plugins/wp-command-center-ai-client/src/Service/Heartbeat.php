<?php
/**
 * Client heartbeat service.
 *
 * @package WPCommandCenterAI\Client
 */

namespace WPCommandCenterAI\Client\Service;

defined( 'ABSPATH' ) || exit;

final class Heartbeat {
	public function send(): void {
		$master_url = untrailingslashit( (string) get_option( 'wpccai_client_master_url', '' ) );
		$secret     = (string) get_option( 'wpccai_client_shared_secret', '' );
		$site_id    = (string) get_option( 'wpccai_client_site_id', '' );

		if ( '' === $master_url || '' === $secret || '' === $site_id || ! wp_http_validate_url( $master_url ) ) {
			return;
		}

		$response = wp_remote_post(
			$master_url . '/wp-json/wp-command-center-ai/v1/heartbeat',
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type'    => 'application/json',
					'X-WPCCAI-Secret' => $secret,
				),
				'body'    => wp_json_encode(
					array(
						'site_id'     => $site_id,
						'site_url'    => home_url( '/' ),
						'wp_version'  => get_bloginfo( 'version' ),
						'php_version' => PHP_VERSION,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			update_option( 'wpccai_client_last_error', $response->get_error_message(), false );
			return;
		}

		update_option( 'wpccai_client_last_status', wp_remote_retrieve_response_code( $response ), false );
		update_option( 'wpccai_client_last_seen', current_time( 'mysql', true ), false );
		delete_option( 'wpccai_client_last_error' );
	}
}
