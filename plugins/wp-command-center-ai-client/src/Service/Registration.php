<?php
/**
 * Client registration workflow.
 *
 * @package WPCommandCenterAI\Client
 */

namespace WPCommandCenterAI\Client\Service;

use WPCommandCenterAI\Client\Capability\CapabilityReporter;
use WPCommandCenterAI\Client\Capability\NegotiatedCapabilityStore;
use WPCommandCenterAI\Client\Security\KeyStore;
use WPCommandCenterAI\Client\Security\MasterKeyStore;
use WPCommandCenterAI\Core\Logging\LoggerInterface;
use WPCommandCenterAI\Core\Security\CryptoException;
use WPCommandCenterAI\Core\Security\Ed25519;
use WPCommandCenterAI\Core\Security\ProtocolMessage;
use WP_Error;

defined( 'ABSPATH' ) || exit;

final class Registration {
	public function __construct(
		private KeyStore $keys,
		private MasterKeyStore $master_keys,
		private CapabilityReporter $capabilities,
		private NegotiatedCapabilityStore $negotiated,
		private LoggerInterface $logger
	) {
	}

	public function run( string $master_url, string $enrollment_token ): true|WP_Error {
		$master_url = untrailingslashit( esc_url_raw( $master_url ) );

		if ( ! wp_http_validate_url( $master_url ) || '' === $enrollment_token ) {
			return new WP_Error( 'wpccai_invalid_registration', 'Master URL and enrollment token are required.' );
		}

		$key_pair = $this->keys->current();
		$manifest = $this->capabilities->manifest();
		$challenge_response = $this->post(
			$master_url . '/wp-json/wp-command-center-ai/v1/registration/challenge',
			array(
				'enrollment_token' => $enrollment_token,
				'site_name'        => get_bloginfo( 'name' ),
				'site_url'         => home_url( '/' ),
				'key_id'           => $key_pair->key_id,
				'public_key'       => $key_pair->public_key,
				'capabilities'     => $manifest->to_array(),
			)
		);

		if ( is_wp_error( $challenge_response ) ) {
			return $challenge_response;
		}

		$challenge_id = sanitize_text_field( (string) ( $challenge_response['challenge_id'] ?? '' ) );
		$challenge    = sanitize_text_field( (string) ( $challenge_response['challenge'] ?? '' ) );
		$challenge_capability_checksum = sanitize_text_field( (string) ( $challenge_response['capability_checksum'] ?? '' ) );
		$master_key_id = sanitize_text_field( (string) ( $challenge_response['master_key_id'] ?? '' ) );
		$master_public_key = sanitize_text_field( (string) ( $challenge_response['master_public_key'] ?? '' ) );

		if (
			'' === $challenge_id
			|| '' === $challenge
			|| ! hash_equals( $manifest->checksum(), $challenge_capability_checksum )
			|| '' === $master_key_id
			|| '' === $master_public_key
		) {
			return new WP_Error( 'wpccai_invalid_challenge', 'The Master returned an incomplete registration challenge.' );
		}

		$complete_response = $this->post(
			$master_url . '/wp-json/wp-command-center-ai/v1/registration/complete',
			array(
				'challenge_id' => $challenge_id,
				'signature'    => Ed25519::sign(
					ProtocolMessage::registration_challenge(
						$challenge_id,
						$challenge,
						$challenge_capability_checksum
					),
					$key_pair->private_key
				),
			)
		);

		if ( is_wp_error( $complete_response ) ) {
			return $complete_response;
		}

		$site_id          = sanitize_text_field( (string) ( $complete_response['site_id'] ?? '' ) );
		$response_key_id  = sanitize_text_field( (string) ( $complete_response['master_key_id'] ?? '' ) );
		$response_public  = sanitize_text_field( (string) ( $complete_response['master_public_key'] ?? '' ) );
		$master_signature = sanitize_text_field( (string) ( $complete_response['master_signature'] ?? '' ) );
		$accepted          = (array) ( $complete_response['capabilities'] ?? array() );
		$capability_checksum = sanitize_text_field( (string) ( $complete_response['capability_checksum'] ?? '' ) );

		if (
			'' === $site_id
			|| ! hash_equals( $master_key_id, $response_key_id )
			|| ! hash_equals( $master_public_key, $response_public )
			|| ! Ed25519::verify(
				ProtocolMessage::registration_proof(
					$challenge_id,
					$site_id,
					$key_pair->key_id,
					$response_key_id,
					$capability_checksum
				),
				$master_signature,
				$response_public
			)
			|| ! $this->negotiated->store( $accepted, $capability_checksum )
		) {
			return new WP_Error( 'wpccai_invalid_master_proof', 'The Master registration proof is invalid.' );
		}

		try {
			$this->master_keys->trust( $response_key_id, $response_public );
		} catch ( CryptoException $exception ) {
			return new WP_Error( 'wpccai_invalid_master_key', $exception->getMessage() );
		}
		update_option( 'wpccai_client_site_id', $site_id, false );
		update_option( 'wpccai_client_master_url', $master_url, false );
		update_option( 'wpccai_client_registered_at', time(), false );
		delete_option( 'wpccai_client_shared_secret' );
		delete_option( 'wpccai_client_enrollment_token' );

		$this->logger->notice( 'Client registration completed for {site_id}.', array( 'site_id' => $site_id ) );

		return true;
	}

	private function post( string $url, array $body ): array|WP_Error {
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 || ! is_array( $data ) ) {
			return new WP_Error(
				'wpccai_registration_failed',
				is_array( $data ) && isset( $data['message'] ) ? (string) $data['message'] : 'Registration request failed.'
			);
		}

		return $data;
	}
}
