<?php
/**
 * Inventory synchronization service.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Inventory;

use WPCommandCenterAI\Core\Inventory\InventoryNormalizer;
use WPCommandCenterAI\Core\Logging\LoggerInterface;

defined( 'ABSPATH' ) || exit;

final class InventorySynchronizer {
	public function __construct(
		private InventoryNormalizer $normalizer,
		private InventoryRepository $repository,
		private LoggerInterface $logger
	) {
	}

	public function synchronize( string $site_id, array $payload ): bool {
		if ( empty( $payload ) ) {
			return false;
		}

		$snapshot = $this->normalizer->normalize( $site_id, $payload );
		$changed  = $this->repository->synchronize( $snapshot );

		if ( $changed ) {
			$this->logger->info(
				'Inventory synchronized for {site_id} with checksum {checksum}.',
				array(
					'site_id'  => $site_id,
					'checksum' => $snapshot->checksum,
				)
			);
		}

		return $changed;
	}
}
