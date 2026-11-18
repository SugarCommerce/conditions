<?php
/**
 * The window a velocity rule counts within.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Tests\Helpers;

use SugarCommerce\Conditions\Conditions;
use SugarCommerce\Conditions\Helpers\Velocity;
use PHPUnit\Framework\TestCase;

/**
 * Class VelocityWindowTest
 */
final class VelocityTest extends TestCase {

	protected function setUp(): void {
		Conditions::reset();
		wpc_reset();
	}

	protected function tearDown(): void {
		Conditions::reset();
		wpc_reset();
	}

	/**
	 * The rule's number is the threshold, never the window.
	 *
	 * It used to be both, so "≥ 3 in [minute]" counted the last three
	 * minutes. The unit alone is the window: a preset is so many of it and a
	 * bare unit is one.
	 */
	public function test_the_window_comes_from_the_unit_alone(): void {
		$this->assertSame( [ 10, 'minute' ], Velocity::resolve_window( [
			'_unit' => '10_minutes',
			'_number' => 3,
		] ) );
		$this->assertSame( [ 6, 'hour' ], Velocity::resolve_window( [
			'_unit' => '6_hours',
			'_number' => 50,
		] ) );
		$this->assertSame( [ 1, 'hour' ], Velocity::resolve_window( [
			'_unit' => 'hour',
			'_number' => 5,
		] ) );
		$this->assertSame( [ 1, 'day' ], Velocity::resolve_window( [ '_unit' => 'day' ] ) );
		$this->assertSame( [ 1, 'hour' ], Velocity::resolve_window( [] ) );
		$this->assertSame( 'INTERVAL 10 MINUTE', Velocity::to_interval( ...Velocity::resolve_window( [ '_unit' => '10_minutes' ] ) ) );
	}

	/**
	 * A count comes from the host, and no host means no answer.
	 *
	 * A count of nought for a card nobody could look up would satisfy every
	 * "fewer than" rule.
	 */
	public function test_a_card_count_comes_from_the_host(): void {
		$this->assertSame( 4, Velocity::count( 'orders_by_card_fingerprint', [
			'card_fingerprint'                    => 'abc',
			'velocity_orders_by_card_fingerprint' => 4,
		] ) );
		$this->assertNull( Velocity::count( 'orders_by_card_fingerprint', [ 'card_fingerprint' => 'abc' ] ) );
		$this->assertNull( Velocity::count( 'orders_by_card_fingerprint', [ 'velocity_orders_by_card_fingerprint' => 4 ] ), 'No fingerprint, no count.' );
		$this->assertNull( Velocity::count( 'orders_by_ip', [ 'ip' => '203.0.113.1' ] ), 'Nothing counts orders, so nothing answers.' );
		$this->assertNull( Velocity::count( 'not_a_kind', [ 'ip' => '203.0.113.1' ] ) );
	}

	/**
	 * The filter answers with the rule's own window, and before an argument.
	 */
	public function test_the_filter_is_handed_the_rule_window_and_wins(): void {
		$seen = [];

		add_filter(
			Conditions::hook( 'velocity_blocked_attempts_by_ip' ),
			static function ( $count, $ip, $number, $unit ) use ( &$seen ) {
				$seen = [ $ip, $number, $unit ];

				return 7;
			}
		);

		$args = [
			'ip'                              => '203.0.113.1',
			'_unit'                           => '15_minutes',
			'velocity_blocked_attempts_by_ip' => 1,
		];

		$this->assertSame( 7, Velocity::count( 'blocked_attempts_by_ip', $args ) );
		$this->assertSame( [ '203.0.113.1', 15, 'minute' ], $seen );
	}

	/**
	 * The filter is named after the prefix, so two plugins' copies do not
	 * answer each other's rules.
	 */
	public function test_the_filter_is_named_after_the_prefix(): void {
		Conditions::use_prefix( 'shop' );

		add_filter( 'conditions_velocity_orders_by_email', static fn() => 99 );
		add_filter( 'shop_conditions_velocity_orders_by_email', static fn() => 2 );

		$this->assertSame( 2, Velocity::count( 'orders_by_email', [ 'email' => 'a@example.com' ] ) );
	}

	/**
	 * The same-product counts are handed the cart's products, from either
	 * argument, and have no answer for an empty cart.
	 */
	public function test_same_product_counts_are_handed_the_products(): void {
		$seen = null;

		add_filter(
			Conditions::hook( 'velocity_same_product_orders_by_ip' ),
			static function ( $count, $ip, $products ) use ( &$seen ) {
				$seen = $products;

				return 3;
			}
		);

		$this->assertSame( 3, Velocity::count( 'same_product_orders_by_ip', [ 'ip' => '203.0.113.1', 'cart_product_ids' => [ 12, '7', 'x' ] ] ) );
		$this->assertSame( [ 12, 7 ], $seen );
		$this->assertNull( Velocity::count( 'same_product_orders_by_ip', [ 'ip' => '203.0.113.1' ] ) );
	}

	/**
	 * A fingerprint is a salted hash of the card's details, never the details.
	 */
	public function test_a_card_fingerprint_is_stable_and_hides_the_card(): void {
		$one = Velocity::compute_card_fingerprint( 'Visa', '4242', null, null, 'GB' );

		$this->assertSame( $one, Velocity::compute_card_fingerprint( 'visa ', '4242', null, null, 'gb' ) );
		$this->assertNotSame( $one, Velocity::compute_card_fingerprint( 'visa', '4243', null, null, 'GB' ) );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{40}$/', $one );
		$this->assertStringNotContainsString( '4242', $one );
	}
}
