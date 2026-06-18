<?php
/**
 * Declarative capability requirement evaluator.
 *
 * @package WPCommandCenterAI\Core
 */

namespace WPCommandCenterAI\Core\Capability;

final class RequirementEvaluator {
	public function supports( Capability $capability, FeatureSet $features ): bool {
		$requirements = (array) ( $capability->metadata['requires'] ?? array() );

		foreach ( (array) ( $requirements['extensions'] ?? array() ) as $extension ) {
			if ( ! $features->has_extension( (string) $extension ) ) {
				return false;
			}
		}

		foreach ( (array) ( $requirements['functions'] ?? array() ) as $function ) {
			if ( ! $features->has_function( (string) $function ) ) {
				return false;
			}
		}

		foreach ( (array) ( $requirements['classes'] ?? array() ) as $class ) {
			if ( ! $features->has_class( (string) $class ) ) {
				return false;
			}
		}

		foreach ( (array) ( $requirements['constants'] ?? array() ) as $constant ) {
			if ( ! $features->has_constant( (string) $constant ) ) {
				return false;
			}
		}

		foreach ( (array) ( $requirements['flags'] ?? array() ) as $flag ) {
			if ( ! $features->flag( (string) $flag ) ) {
				return false;
			}
		}

		return true;
	}
}
