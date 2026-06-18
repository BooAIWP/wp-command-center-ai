<?php
/**
 * REST route provider contract.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Rest;

interface RestRouteProviderInterface {
	public function register_routes(): void;
}
