<?php
/**
 * Client capability discovery.
 *
 * @package WPCommandCenterAI\Client
 */

namespace WPCommandCenterAI\Client\Capability;

use WPCommandCenterAI\Core\Capability\CapabilityDiscovery;
use WPCommandCenterAI\Core\Capability\CapabilityManifest;
use WPCommandCenterAI\Core\Capability\FeatureSet;

defined( 'ABSPATH' ) || exit;

final class CapabilityReporter {
	public function __construct( private CapabilityDiscovery $discovery ) {
	}

	public function manifest( string $site_id = '' ): CapabilityManifest {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$functions = get_defined_functions();

		return $this->discovery->discover(
			$site_id,
			new FeatureSet(
				get_loaded_extensions(),
				array_merge( $functions['internal'] ?? array(), $functions['user'] ?? array() ),
				get_declared_classes(),
				array_keys( get_defined_constants() ),
				array(
					'multisite' => is_multisite(),
					'https'     => is_ssl(),
					'cron'      => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
				)
			)
		);
	}
}
