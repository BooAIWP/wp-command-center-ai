<?php
/**
 * Client heartbeat REST controller.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Rest;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class HeartbeatController {
	private const NAMESPACE = 'wp-command-center-ai/v1';

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/heartbeat',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'receive' ),
				'permission_callback' => array( $this, 'authorize' ),
			)
		);
	}

	public function authorize( WP_REST_Request $request ): bool|WP_Error {
		$provided = (string) $request->get_header( 'X-WPCCAI-Secret' );
		$expected = (string) get_option( 'wpccai_master_shared_secret', '' );

		if ( '' === $provided || '' === $expected || ! hash_equals( $expected, $provided ) ) {
			return new WP_Error(
				'wpccai_unauthorized',
				__( 'Invalid command center credentials.', 'wp-command-center-ai-master' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	public function receive( WP_REST_Request $request ): WP_REST_Response {
		$site_id = sanitize_key( (string) $request->get_param( 'site_id' ) );

		if ( '' === $site_id ) {
			return new WP_REST_Response( array( 'message' => 'site_id is required.' ), 400 );
		}

		$clients = get_option( 'wpccai_master_clients', array() );
		$clients = is_array( $clients ) ? $clients : array();

		$clients[ $site_id ] = array(
			'site_url'    => esc_url_raw( (string) $request->get_param( 'site_url' ) ),
			'wp_version'  => sanitize_text_field( (string) $request->get_param( 'wp_version' ) ),
			'php_version' => sanitize_text_field( (string) $request->get_param( 'php_version' ) ),
			'last_seen'   => current_time( 'mysql', true ),
		);

		update_option( 'wpccai_master_clients', $clients, false );

		return new WP_REST_Response(
			array(
				'accepted'  => true,
				'server_at' => current_time( 'mysql', true ),
			)
		);
	}
}
