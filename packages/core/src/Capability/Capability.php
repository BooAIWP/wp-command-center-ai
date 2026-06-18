<?php
/**
 * Capability value object.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Capability;

final class Capability {
	public function __construct(
		public readonly string $id,
		public readonly string $label,
		public readonly string $description = '',
		public readonly array $metadata = array()
	) {
	}
}
