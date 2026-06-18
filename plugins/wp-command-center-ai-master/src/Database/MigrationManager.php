<?php
/**
 * Fleet data migrations.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Database;

use WPCommandCenterAI\Master\Client\ClientRepository;

defined( 'ABSPATH' ) || exit;

final class MigrationManager {
	public function __construct(
		private Schema $schema,
		private ClientRepository $clients
	) {
	}

	public function migrate(): void {
		if ( absint( get_option( 'wpccai_master_schema_version', 0 ) ) < Schema::VERSION ) {
			$this->schema->migrate();
		}

		if ( ! get_option( 'wpccai_master_legacy_clients_migrated', false ) ) {
			$this->migrate_legacy_clients();
			update_option( 'wpccai_master_legacy_clients_migrated', true, false );
		}
	}

	private function migrate_legacy_clients(): void {
		$legacy_clients = get_option( 'wpccai_master_clients', array() );

		if ( ! is_array( $legacy_clients ) ) {
			return;
		}

		foreach ( $legacy_clients as $site_id => $client ) {
			if ( ! is_array( $client ) || null !== $this->clients->find( (string) $site_id ) ) {
				continue;
			}

			$this->clients->import_legacy( (string) $site_id, $client );
		}
	}
}
