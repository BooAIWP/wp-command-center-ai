<?php
/**
 * Capability negotiation and synchronization.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Capability;

use InvalidArgumentException;
use WPCommandCenterAI\Core\Capability\CapabilityManifest;
use WPCommandCenterAI\Core\Capability\CapabilityNegotiator;
use WPCommandCenterAI\Core\Logging\LoggerInterface;

defined( 'ABSPATH' ) || exit;

final class CapabilitySynchronizer {
	public function __construct(
		private CapabilityNegotiator $negotiator,
		private CapabilityPolicy $policy,
		private CapabilityRepository $repository,
		private LoggerInterface $logger
	) {
	}

	public function synchronize( string $site_id, array $payload ): array {
		$result = $this->evaluate( $payload, $site_id );

		$this->repository->synchronize( $result['manifest'], $result['accepted'] );
		$this->logger->info(
			'Capabilities synchronized for {site_id}.',
			array(
				'site_id' => $site_id,
				'count'   => count( $result['accepted'] ),
			)
		);

		unset( $result['manifest'] );

		return $result;
	}

	public function evaluate( array $payload, string $site_id = '' ): array {
		$manifest   = CapabilityManifest::from_array( $payload, $site_id );
		$provided_checksum = (string) ( $payload['checksum'] ?? '' );

		if ( '' === $provided_checksum || ! hash_equals( $manifest->checksum(), $provided_checksum ) ) {
			throw new InvalidArgumentException( 'Capability manifest checksum is invalid.' );
		}

		$accepted   = $this->negotiator->accepted( $manifest, $this->policy->requirements() );
		$missing    = $this->negotiator->missing( $manifest, $this->policy->requirements() );
		$checksum   = CapabilityManifest::checksum_for( $accepted );

		return array(
			'manifest' => $manifest,
			'accepted' => $accepted,
			'missing'  => $missing,
			'checksum' => $checksum,
		);
	}
}
