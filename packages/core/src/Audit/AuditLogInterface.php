<?php
/**
 * Audit log contract.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Audit;

interface AuditLogInterface {
	public function append( AuditEvent $event ): void;

	public function search( array $filters = array(), int $limit = 100, int $offset = 0 ): array;
}
