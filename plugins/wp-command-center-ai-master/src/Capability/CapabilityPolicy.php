<?php
/**
 * Master capability policy.
 *
 * @package WPCommandCenterAI\Master
 */

namespace WPCommandCenterAI\Master\Capability;

defined( 'ABSPATH' ) || exit;

final class CapabilityPolicy {
	public function requirements(): array {
		return array(
			'protocol.registration' => '1.0.0',
			'protocol.heartbeat'    => '1.0.0',
			'inventory.snapshot'    => '1.0.0',
		);
	}
}
