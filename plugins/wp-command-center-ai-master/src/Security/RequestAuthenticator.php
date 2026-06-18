<?php
/**
 * Signed client request authentication.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Security;

use WPCommandCenterAI\Core\Security\RequestSignature;
use WPCommandCenterAI\Master\Client\ClientRepository;
use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

final class RequestAuthenticator {
	private const CLOCK_SKEW = 300;

	public function __construct( private ClientRepository $clients ) {
	}

	public function authenticate( WP_REST_Request $request ): bool|WP_Error {
		$site_id   = sanitize_text_field( (string) $request->get_header( 'X-WPCCAI-Site-ID' ) );
		$key_id    = sanitize_text_field( (string) $request->get_header( 'X-WPCCAI-Key-ID' ) );
		$timestamp = absint( $request->get_header( 'X-WPCCAI-Timestamp' ) );
		$nonce     = sanitize_text_field( (string) $request->get_header( 'X-WPCCAI-Nonce' ) );
		$signature = sanitize_text_field( (string) $request->get_header( 'X-WPCCAI-Signature' ) );
		$public_key = $this->clients->public_key( $site_id, $key_id );

		if ( null === $public_key ) {
			return $this->error( 'unknown_key', 'Unknown client signing key.' );
		}

		if ( 0 === $timestamp || abs( time() - $timestamp ) > self::CLOCK_SKEW ) {
			return $this->error( 'expired_request', 'The signed request timestamp is outside the allowed window.' );
		}

		if ( '' === $nonce || get_transient( $this->nonce_key( $site_id, $nonce ) ) ) {
			return $this->error( 'replayed_request', 'The request nonce is invalid or has already been used.' );
		}

		$request_signature = new RequestSignature( $key_id, $timestamp, $nonce, $signature );

		if (
			! $request_signature->verify(
				$request->get_method(),
				$request->get_route(),
				$request->get_body(),
				$public_key
			)
		) {
			return $this->error( 'invalid_signature', 'The request signature is invalid.' );
		}

		set_transient( $this->nonce_key( $site_id, $nonce ), 1, self::CLOCK_SKEW * 2 );

		return true;
	}

	private function nonce_key( string $site_id, string $nonce ): string {
		return 'wpccai_nonce_' . md5( $site_id . ':' . $nonce );
	}

	private function error( string $code, string $message ): WP_Error {
		return new WP_Error( 'wpccai_' . $code, $message, array( 'status' => 401 ) );
	}
}
