<?php
/**
 * Client heartbeat REST controller.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Rest;

use WPCommandCenterAI\Core\Logging\LoggerInterface;
use WPCommandCenterAI\Core\Rest\RestRouteProviderInterface;
use WPCommandCenterAI\Core\Security\Ed25519;
use WPCommandCenterAI\Core\Security\ProtocolMessage;
use WPCommandCenterAI\Master\Client\ClientRepository;
use WPCommandCenterAI\Master\Inventory\InventorySynchronizer;
use WPCommandCenterAI\Master\Security\KeyStore;
use WPCommandCenterAI\Master\Security\RequestAuthenticator;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class HeartbeatController implements RestRouteProviderInterface {
	private const NAMESPACE = 'wp-command-center-ai/v1';

	public function __construct(
		private RequestAuthenticator $authenticator,
		private ClientRepository $clients,
		private InventorySynchronizer $inventory,
		private KeyStore $keys,
		private LoggerInterface $logger
	) {
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

	public function authorize( WP_REST_Request $request ): bool|\WP_Error {
		return $this->authenticator->authenticate( $request );
	}

	public function receive( WP_REST_Request $request ): WP_REST_Response {
		$site_id = sanitize_text_field( (string) $request->get_header( 'X-WPCCAI-Site-ID' ) );
		$nonce   = sanitize_text_field( (string) $request->get_header( 'X-WPCCAI-Nonce' ) );

		$payload = $request->get_json_params();

		if ( ! $this->clients->record_heartbeat( $site_id, $payload ) ) {
			return new WP_REST_Response( array( 'message' => 'Unknown client.' ), 404 );
		}

		$this->inventory->synchronize( $site_id, (array) ( $payload['inventory'] ?? array() ) );

		$master_key      = $this->keys->current();
		$next_master_key = $this->keys->prepare_rotation();
		$server_timestamp = time();
		$receipt          = ProtocolMessage::heartbeat_receipt(
			$site_id,
			$nonce,
			$server_timestamp,
			$master_key->key_id
		);
		$this->logger->info(
			'Client heartbeat accepted for {site_id}.',
			array( 'site_id' => $site_id )
		);

		$response = array(
				'accepted'         => true,
				'server_timestamp' => $server_timestamp,
				'master_key_id'    => $master_key->key_id,
				'signature'        => Ed25519::sign( $receipt, $master_key->private_key ),
				'key_rotation_due' => $this->keys->rotation_due(),
		);

		if ( null !== $next_master_key ) {
			$response['next_master_key_id']     = $next_master_key->key_id;
			$response['next_master_public_key'] = $next_master_key->public_key;
			$response['key_update_signature']   = Ed25519::sign(
				ProtocolMessage::key_rotation(
					$master_key->key_id,
					$next_master_key->key_id,
					$next_master_key->public_key
				),
				$master_key->private_key
			);
		}

		return new WP_REST_Response( $response );
	}
}
