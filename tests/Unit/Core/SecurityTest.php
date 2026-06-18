<?php
/**
 * Security protocol tests.
 *
 * @package WPCommandCenterAI\Tests
 */

namespace WPCommandCenterAI\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use WPCommandCenterAI\Core\Security\Base64Url;
use WPCommandCenterAI\Core\Security\CanonicalRequest;
use WPCommandCenterAI\Core\Security\Ed25519;
use WPCommandCenterAI\Core\Security\RequestSignature;
use WPCommandCenterAI\Core\Security\RotationPolicy;
use WPCommandCenterAI\Core\Security\SecretBox;

final class SecurityTest extends TestCase {
	protected function setUp(): void {
		if ( ! Ed25519::available() ) {
			self::markTestSkipped( 'The Sodium extension is not available.' );
		}
	}

	public function test_it_generates_and_verifies_signatures(): void {
		$key_pair = Ed25519::generate_key_pair( 100 );
		$request  = RequestSignature::create( 'POST', '/test', '{"ok":true}', $key_pair, 200, 'nonce' );

		self::assertTrue( $request->verify( 'POST', '/test', '{"ok":true}', $key_pair->public_key ) );
		self::assertFalse( $request->verify( 'POST', '/test', '{"ok":false}', $key_pair->public_key ) );
	}

	public function test_canonical_requests_are_deterministic(): void {
		self::assertSame(
			CanonicalRequest::build( 'post', 'test', 100, 'nonce', 'body' ),
			CanonicalRequest::build( 'POST', '/test', 100, 'nonce', 'body' )
		);
	}

	public function test_base64_url_round_trip(): void {
		$value = random_bytes( 32 );

		self::assertSame( $value, Base64Url::decode( Base64Url::encode( $value ) ) );
	}

	public function test_rotation_policy_exposes_due_and_grace_windows(): void {
		$policy = new RotationPolicy( 100, 20 );

		self::assertFalse( $policy->rotation_due( 1000, 1099 ) );
		self::assertTrue( $policy->rotation_due( 1000, 1100 ) );
		self::assertFalse( $policy->grace_expired( 1000, 1019 ) );
		self::assertTrue( $policy->grace_expired( 1000, 1020 ) );
	}

	public function test_secret_box_encrypts_and_authenticates_private_material(): void {
		$key        = SecretBox::derive_key( 'test material' );
		$ciphertext = SecretBox::encrypt( 'private value', $key );

		self::assertNotSame( 'private value', $ciphertext );
		self::assertSame( 'private value', SecretBox::decrypt( $ciphertext, $key ) );
	}
}
