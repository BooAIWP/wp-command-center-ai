<?php
/**
 * Synchronous event bus.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Event;

final class EventBus {
	/**
	 * @var array<string, array<int, array<int, callable>>>
	 */
	private array $listeners = array();

	public function listen( string $event, callable $listener, int $priority = 10 ): void {
		$this->listeners[ $event ][ $priority ][] = $listener;
	}

	public function dispatch( object|string $event, mixed $payload = null ): object|string {
		$event_name = is_object( $event ) ? $event::class : $event;
		$listeners  = $this->listeners[ $event_name ] ?? array();

		ksort( $listeners );

		foreach ( $listeners as $priority_listeners ) {
			foreach ( $priority_listeners as $listener ) {
				$listener( is_object( $event ) ? $event : $payload, $event_name );
			}
		}

		return $event;
	}

	public function has_listeners( string $event ): bool {
		return ! empty( $this->listeners[ $event ] );
	}
}
