<?php
/**
 * Registered client persistence.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Client;

use WPCommandCenterAI\Core\Status\ClientStatusDetector;
use WPCommandCenterAI\Core\Security\RotationPolicy;

defined( 'ABSPATH' ) || exit;

final class ClientRepository {
	private const OPTION_NAME = 'wpccai_master_clients';

	public function __construct( private ClientStatusDetector $status_detector ) {
	}

	public function all(): array {
		$clients = get_option( self::OPTION_NAME, array() );

		return is_array( $clients ) ? $clients : array();
	}

	public function find( string $site_id ): ?array {
		$clients = $this->all();

		return isset( $clients[ $site_id ] ) && is_array( $clients[ $site_id ] )
			? $clients[ $site_id ]
			: null;
	}

	public function register( array $registration ): array {
		$clients = $this->all();
		$site_id = wp_generate_uuid4();

		$clients[ $site_id ] = array(
			'site_id'           => $site_id,
			'site_name'         => sanitize_text_field( (string) $registration['site_name'] ),
			'site_url'          => esc_url_raw( (string) $registration['site_url'] ),
			'current_key_id'    => sanitize_text_field( (string) $registration['key_id'] ),
			'public_keys'       => array(
				sanitize_text_field( (string) $registration['key_id'] ) => array(
					'public_key' => sanitize_text_field( (string) $registration['public_key'] ),
					'created_at' => time(),
					'retired_at' => null,
				),
			),
			'registered_at'     => time(),
			'last_seen_at'      => null,
			'last_report'       => array(),
			'rotation_due_at'   => time() + 7776000,
		);

		update_option( self::OPTION_NAME, $clients, false );

		return $clients[ $site_id ];
	}

	public function record_heartbeat( string $site_id, array $report ): bool {
		$clients = $this->all();

		if ( ! isset( $clients[ $site_id ] ) ) {
			return false;
		}

		$clients[ $site_id ]['site_name']      = sanitize_text_field( (string) ( $report['site_name'] ?? '' ) );
		$clients[ $site_id ]['site_url']       = esc_url_raw( (string) ( $report['site_url'] ?? '' ) );
		$clients[ $site_id ]['last_seen_at']   = time();
		$clients[ $site_id ]['last_report']    = $this->sanitize_report( $report );

		$next_key_id     = sanitize_text_field( (string) ( $report['next_key_id'] ?? '' ) );
		$next_public_key = sanitize_text_field( (string) ( $report['next_public_key'] ?? '' ) );

		if ( '' !== $next_key_id && '' !== $next_public_key ) {
			$current_key_id = (string) $clients[ $site_id ]['current_key_id'];

			if ( $current_key_id !== $next_key_id ) {
				if ( isset( $clients[ $site_id ]['public_keys'][ $current_key_id ] ) ) {
					$current_key = $clients[ $site_id ]['public_keys'][ $current_key_id ];

					if ( is_array( $current_key ) ) {
						$clients[ $site_id ]['public_keys'][ $current_key_id ]['retired_at'] = time();
					}
				}

				$clients[ $site_id ]['public_keys'][ $next_key_id ] = array(
					'public_key' => $next_public_key,
					'created_at' => time(),
					'retired_at' => null,
				);
				$clients[ $site_id ]['current_key_id']              = $next_key_id;
				$clients[ $site_id ]['rotation_due_at']             = time() + RotationPolicy::DEFAULT_INTERVAL;
			}
		}

		return update_option( self::OPTION_NAME, $clients, false );
	}

	public function public_key( string $site_id, string $key_id ): ?string {
		$client = $this->find( $site_id );

		if ( null === $client || empty( $client['public_keys'][ $key_id ] ) ) {
			return null;
		}

		$key = $client['public_keys'][ $key_id ];

		if ( is_string( $key ) ) {
			return $key;
		}

		$retired_at = absint( $key['retired_at'] ?? 0 );

		if ( 0 !== $retired_at && ( new RotationPolicy() )->grace_expired( $retired_at ) ) {
			return null;
		}

		return isset( $key['public_key'] ) ? (string) $key['public_key'] : null;
	}

	public function status( array $client, ?int $now = null ): string {
		$last_seen = absint( $client['last_seen_at'] ?? 0 );

		return $this->status_detector->detect( 0 === $last_seen ? null : $last_seen, $now );
	}

	public function counts( ?int $now = null ): array {
		$counts = array(
			'online'  => 0,
			'stale'   => 0,
			'offline' => 0,
		);

		foreach ( $this->all() as $client ) {
			++$counts[ $this->status( $client, $now ) ];
		}

		return $counts;
	}

	private function sanitize_report( array $report ): array {
		return array(
			'wp_version'     => sanitize_text_field( (string) ( $report['wp_version'] ?? '' ) ),
			'php_version'    => sanitize_text_field( (string) ( $report['php_version'] ?? '' ) ),
			'client_version' => sanitize_text_field( (string) ( $report['client_version'] ?? '' ) ),
		);
	}
}
