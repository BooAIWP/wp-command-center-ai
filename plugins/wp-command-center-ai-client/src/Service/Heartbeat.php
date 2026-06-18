<?php
/**
 * Client heartbeat service.
 *
 * @package WPCommandCenterAI\Client
 */

namespace WPCommandCenterAI\Client\Service;

use WPCommandCenterAI\Client\Security\KeyStore;
use WPCommandCenterAI\Client\Security\MasterKeyStore;
use WPCommandCenterAI\Core\Logging\LoggerInterface;
use WPCommandCenterAI\Core\Security\Ed25519;
use WPCommandCenterAI\Core\Security\ProtocolMessage;
use WPCommandCenterAI\Core\Security\RequestSignature;
use WPCommandCenterAI\Core\Security\CryptoException;

defined( 'ABSPATH' ) || exit;

final class Heartbeat {
	private const ROUTE = '/wp-command-center-ai/v1/heartbeat';

	public function __construct(
		private KeyStore $keys,
		private MasterKeyStore $master_keys,
		private LoggerInterface $logger
	) {
	}

	public function send(): void {
		$master_url = untrailingslashit( (string) get_option( 'wpccai_client_master_url', '' ) );
		$site_id    = (string) get_option( 'wpccai_client_site_id', '' );

		if ( '' === $master_url || '' === $site_id || ! wp_http_validate_url( $master_url ) ) {
			$this->logger->debug( 'Heartbeat skipped because the client is not configured.' );
			return;
		}

		$key_pair = $this->keys->current();
		$next_key = $this->keys->prepare_rotation();
		$body     = (string) wp_json_encode(
			array(
				'site_name'       => get_bloginfo( 'name' ),
				'site_url'        => home_url( '/' ),
				'wp_version'      => get_bloginfo( 'version' ),
				'php_version'     => PHP_VERSION,
				'client_version'  => WPCCAI_CLIENT_VERSION,
				'next_key_id'     => $next_key?->key_id,
				'next_public_key' => $next_key?->public_key,
			)
		);
		$signed_request = RequestSignature::create( 'POST', self::ROUTE, $body, $key_pair );

		$response = wp_remote_post(
			$master_url . '/wp-json' . self::ROUTE,
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type'       => 'application/json',
					'X-WPCCAI-Site-ID'  => $site_id,
					'X-WPCCAI-Key-ID'   => $signed_request->key_id,
					'X-WPCCAI-Timestamp' => (string) $signed_request->timestamp,
					'X-WPCCAI-Nonce'    => $signed_request->nonce,
					'X-WPCCAI-Signature' => $signed_request->signature,
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			update_option( 'wpccai_client_last_error', $response->get_error_message(), false );
			$this->logger->error(
				'Heartbeat request failed: {message}.',
				array( 'message' => $response->get_error_message() )
			);
			return;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		update_option( 'wpccai_client_last_status', $status_code, false );

		if ( 200 !== $status_code || ! is_array( $response_body ) || ! $this->verify_receipt( $site_id, $signed_request->nonce, $response_body ) ) {
			update_option( 'wpccai_client_last_error', 'The Master heartbeat receipt is invalid.', false );
			$this->logger->error( 'Heartbeat response verification failed.' );
			return;
		}

		$this->accept_master_key_update( $response_body );

		if ( null !== $next_key ) {
			$this->keys->promote( $next_key->key_id );
		}

		update_option( 'wpccai_client_last_seen', time(), false );
		delete_option( 'wpccai_client_last_error' );
		$this->logger->info(
			'Heartbeat request completed with status {status}.',
			array( 'status' => $status_code )
		);
	}

	private function verify_receipt( string $site_id, string $nonce, array $response ): bool {
		$key_id    = sanitize_text_field( (string) ( $response['master_key_id'] ?? '' ) );
		$timestamp = absint( $response['server_timestamp'] ?? 0 );
		$signature = sanitize_text_field( (string) ( $response['signature'] ?? '' ) );
		$public_key = $this->master_keys->find( $key_id );

		if ( null === $public_key || 0 === $timestamp || abs( time() - $timestamp ) > 300 ) {
			return false;
		}

		return Ed25519::verify(
			ProtocolMessage::heartbeat_receipt( $site_id, $nonce, $timestamp, $key_id ),
			$signature,
			$public_key
		);
	}

	private function accept_master_key_update( array $response ): void {
		$current_key_id = sanitize_text_field( (string) ( $response['master_key_id'] ?? '' ) );
		$next_key_id    = sanitize_text_field( (string) ( $response['next_master_key_id'] ?? '' ) );
		$next_public_key = sanitize_text_field( (string) ( $response['next_master_public_key'] ?? '' ) );
		$signature       = sanitize_text_field( (string) ( $response['key_update_signature'] ?? '' ) );
		$current_public  = $this->master_keys->find( $current_key_id );

		if (
			null !== $current_public
			&& '' !== $next_key_id
			&& '' !== $next_public_key
			&& Ed25519::verify(
				ProtocolMessage::key_rotation( $current_key_id, $next_key_id, $next_public_key ),
				$signature,
				$current_public
			)
		) {
			try {
				$this->master_keys->trust( $next_key_id, $next_public_key );
			} catch ( CryptoException $exception ) {
				$this->logger->warning(
					'Master key update was rejected: {message}.',
					array( 'message' => $exception->getMessage() )
				);
			}
		}
	}
}
