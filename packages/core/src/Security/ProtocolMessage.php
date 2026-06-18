<?php
/**
 * Canonical protocol messages.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Security;

final class ProtocolMessage {
	public static function registration_challenge(
		string $challenge_id,
		string $challenge,
		string $capability_checksum = ''
	): string {
		return "registration-challenge\n{$challenge_id}\n{$challenge}\n{$capability_checksum}";
	}

	public static function registration_proof(
		string $challenge_id,
		string $site_id,
		string $client_key_id,
		string $master_key_id,
		string $capability_checksum = ''
	): string {
		return "registration-proof\n{$challenge_id}\n{$site_id}\n{$client_key_id}\n{$master_key_id}\n{$capability_checksum}";
	}

	public static function heartbeat_receipt(
		string $site_id,
		string $request_nonce,
		int $server_timestamp,
		string $master_key_id,
		string $capability_checksum = ''
	): string {
		return "heartbeat-receipt\n{$site_id}\n{$request_nonce}\n{$server_timestamp}\n{$master_key_id}\n{$capability_checksum}";
	}

	public static function key_rotation(
		string $current_key_id,
		string $next_key_id,
		string $next_public_key
	): string {
		return "key-rotation\n{$current_key_id}\n{$next_key_id}\n{$next_public_key}";
	}

	private function __construct() {
	}
}
