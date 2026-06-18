<?php
/**
 * Normalized inventory snapshot.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Inventory;

final class InventorySnapshot {
	public function __construct(
		public readonly string $site_id,
		public readonly string $checksum,
		public readonly int $collected_at,
		public readonly array $environment,
		public readonly array $wordpress,
		public readonly array $plugins,
		public readonly array $themes
	) {
	}

	public function to_array(): array {
		return array(
			'site_id'      => $this->site_id,
			'checksum'     => $this->checksum,
			'collected_at' => $this->collected_at,
			'environment'  => $this->environment,
			'wordpress'    => $this->wordpress,
			'plugins'      => $this->plugins,
			'themes'       => $this->themes,
		);
	}
}
