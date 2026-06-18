<?php
/**
 * Client registration REST controller.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Rest;

use WPCommandCenterAI\Core\Logging\LoggerInterface;
use WPCommandCenterAI\Core\Rest\RestRouteProviderInterface;
use WPCommandCenterAI\Core\Security\Ed25519;
use WPCommandCenterAI\Core\Security\ProtocolMessage;
use WPCommandCenterAI\Master\Client\ClientRepository;
use WPCommandCenterAI\Master\Security\ChallengeStore;
use WPCommandCenterAI\Master\Security\KeyStore;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class RegistrationController implements RestRouteProviderInterface {
	private const NAMESPACE = 'wp-command-center-ai/v1';

	public function __construct(
		private ChallengeStore $challenges,
		private ClientRepository $clients,
		private KeyStore $keys,
		private LoggerInterface $logger
	) {
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/registration/challenge',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'challenge' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/registration/complete',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'complete' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function challenge( WP_REST_Request $request ): WP_REST_Response {
		$token    = (string) $request->get_param( 'enrollment_token' );
		$expected = (string) get_option( 'wpccai_master_enrollment_token', '' );

		if ( '' === $token || '' === $expected || ! hash_equals( $expected, $token ) ) {
			return new WP_REST_Response( array( 'message' => 'Invalid enrollment token.' ), 401 );
		}

		$site_url   = esc_url_raw( (string) $request->get_param( 'site_url' ) );
		$public_key = sanitize_text_field( (string) $request->get_param( 'public_key' ) );
		$key_id     = sanitize_text_field( (string) $request->get_param( 'key_id' ) );

		if ( '' === $site_url || '' === $public_key || '' === $key_id ) {
			return new WP_REST_Response( array( 'message' => 'Registration data is incomplete.' ), 400 );
		}

		try {
			if ( ! hash_equals( $key_id, Ed25519::key_id_from_public_key( $public_key ) ) ) {
				return new WP_REST_Response( array( 'message' => 'Client key ID does not match the public key.' ), 400 );
			}
		} catch ( \Throwable ) {
			return new WP_REST_Response( array( 'message' => 'Client public key is invalid.' ), 400 );
		}

		$challenge = $this->challenges->issue(
			array(
				'site_name'  => sanitize_text_field( (string) $request->get_param( 'site_name' ) ),
				'site_url'   => $site_url,
				'key_id'     => $key_id,
				'public_key' => $public_key,
			)
		);
		$master_key = $this->keys->current();

		return new WP_REST_Response(
			array_merge(
				$challenge,
				array(
					'master_key_id'     => $master_key->key_id,
					'master_public_key' => $master_key->public_key,
				)
			)
		);
	}

	public function complete( WP_REST_Request $request ): WP_REST_Response {
		$challenge_id = sanitize_text_field( (string) $request->get_param( 'challenge_id' ) );
		$signature    = sanitize_text_field( (string) $request->get_param( 'signature' ) );
		$record       = $this->challenges->consume( $challenge_id );

		if ( null === $record ) {
			return new WP_REST_Response( array( 'message' => 'Registration challenge is missing or expired.' ), 401 );
		}

		if (
			! Ed25519::verify(
				ProtocolMessage::registration_challenge( $challenge_id, (string) $record['challenge'] ),
				$signature,
				(string) $record['public_key']
			)
		) {
			return new WP_REST_Response( array( 'message' => 'Registration challenge signature is invalid.' ), 401 );
		}

		$client     = $this->clients->register( $record );
		$master_key = $this->keys->current();
		$proof      = ProtocolMessage::registration_proof(
			$challenge_id,
			(string) $client['site_id'],
			(string) $record['key_id'],
			$master_key->key_id
		);

		$this->logger->notice(
			'Client registration completed for {site_id}.',
			array( 'site_id' => $client['site_id'] )
		);

		return new WP_REST_Response(
			array(
				'site_id'           => $client['site_id'],
				'master_key_id'     => $master_key->key_id,
				'master_public_key' => $master_key->public_key,
				'master_signature'  => Ed25519::sign( $proof, $master_key->private_key ),
				'rotation_due_at'   => $client['rotation_due_at'],
			),
			201
		);
	}
}
