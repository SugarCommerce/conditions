<?php
/**
 * Velocity Helper
 *
 * Counts orders and attempts by identifier within a time window, for the
 * velocity conditions that catch card-testing, fraud rings and bursts.
 *
 * @package     SugarCommerce\Conditions\Helpers
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Helpers;

use SugarCommerce\Conditions\Conditions;

/**
 * Class Velocity
 *
 * The library keeps no orders, so it counts nothing itself. Each count is
 * answered by, in turn:
 *
 *  1. the `{prefix}_conditions_velocity_{kind}` filter, handed the
 *     identifier and the rule's own window -- the shop or the fraud log that
 *     knows the orders answers here;
 *  2. a `velocity_{kind}` argument the caller worked out beforehand (it
 *     cannot know the rule's window, so it is read as given).
 *
 * With neither, the count is null: the rule cannot be judged and does not
 * match. It used to fall back to counting EDD's or WooCommerce's order
 * tables; the README shows that counter registered by the plugin that
 * knows the shop.
 */
final class Velocity {

	/**
	 * The kinds of count, and the identifier each is keyed on.
	 *
	 * @var array<string, string>
	 */
	public const array KINDS = [
		'orders_by_ip'                       => 'ip',
		'orders_by_email'                    => 'email',
		'same_product_orders_by_ip'          => 'ip',
		'same_product_orders_by_email'       => 'email',
		'distinct_emails_by_ip'              => 'ip',
		'distinct_ips_by_email'              => 'email',
		'failed_orders_by_ip'                => 'ip',
		'failed_orders_by_email'             => 'email',
		'blocked_attempts_by_ip'             => 'ip',
		'blocked_attempts_by_email'          => 'email',
		'orders_by_card_fingerprint'         => 'card_fingerprint',
		'distinct_emails_by_card_fingerprint' => 'card_fingerprint',
	];

	/**
	 * Convert a number_unit pair to a SQL `INTERVAL` clause.
	 *
	 * @param int|float $number The amount.
	 * @param string    $unit   minute|hour|day|week|month|year.
	 *
	 * @return string e.g. "INTERVAL 60 MINUTE"
	 */
	public static function to_interval( int|float $number, string $unit ): string {
		$map = [
			'minute' => 'MINUTE',
			'hour'   => 'HOUR',
			'day'    => 'DAY',
			'week'   => 'WEEK',
			'month'  => 'MONTH',
			'year'   => 'YEAR',
		];

		return sprintf( 'INTERVAL %d %s', max( 1, (int) $number ), $map[ rtrim( $unit, 's' ) ] ?? 'HOUR' );
	}

	/**
	 * The time window a velocity rule counts within.
	 *
	 * The rule's number is the threshold -- "at least 3 orders" -- and used
	 * to double as the window, so "≥ 3 in [minute]" counted the last three
	 * minutes and "≥ 5 in [hour]" the last five hours. The window is the
	 * unit alone now: a preset such as "10_minutes" is ten of that unit, and
	 * a bare unit such as "hour" is one of it.
	 *
	 * @param array $args The condition args.
	 *
	 * @return array{0:int,1:string} Amount and unit.
	 */
	public static function resolve_window( array $args ): array {
		$unit = is_scalar( $args['_unit'] ?? null ) ? (string) $args['_unit'] : 'hour';

		if ( preg_match( '/^(\d+)_(minute|hour|day|week|month|year)s?$/', $unit, $m ) ) {
			return [ max( 1, (int) $m[1] ), $m[2] ];
		}

		$unit = rtrim( $unit, 's' );

		return [ 1, in_array( $unit, [ 'minute', 'hour', 'day', 'week', 'month', 'year' ], true ) ? $unit : 'hour' ];
	}

	/**
	 * One count, for one rule.
	 *
	 * @param string $kind A key of KINDS, e.g. `orders_by_ip`.
	 * @param array  $args Condition args: the identifier the kind is keyed
	 *                     on, `product_ids` for the same-product kinds, and
	 *                     the rule's unit.
	 *
	 * @return int|null The count, or null when nothing can answer.
	 */
	public static function count( string $kind, array $args ): ?int {
		if ( ! isset( self::KINDS[ $kind ] ) ) {
			return null;
		}

		$key   = self::KINDS[ $kind ];
		$value = is_scalar( $args[ $key ] ?? null ) ? trim( (string) $args[ $key ] ) : '';

		if ( '' === $value ) {
			return null;
		}

		[ $number, $unit ] = self::resolve_window( $args );

		$products = str_starts_with( $kind, 'same_product_' ) ? self::product_ids( $args ) : null;

		if ( [] === $products ) {
			return null;
		}

		/**
		 * Answer a velocity count: `{prefix}_conditions_velocity_{kind}`.
		 *
		 * Return a number to answer, or null to leave it. The same-product
		 * kinds are handed the products after the identifier.
		 *
		 * @param int|null $count  The count, or null to fall through.
		 * @param string   $value  The IP, email or card fingerprint.
		 * @param int      $number Time window amount.
		 * @param string   $unit   Time window unit.
		 * @param array    $args   Every condition argument.
		 */
		$filtered = null === $products
			? apply_filters( Conditions::hook( 'velocity_' . $kind ), null, $value, $number, $unit, $args )
			: apply_filters( Conditions::hook( 'velocity_' . $kind ), null, $value, $products, $number, $unit, $args );

		if ( is_numeric( $filtered ) ) {
			return (int) $filtered;
		}

		return is_numeric( $args[ 'velocity_' . $kind ] ?? null ) ? (int) $args[ 'velocity_' . $kind ] : null;
	}

	/**
	 * The products in the cart being judged, from `product_ids` or
	 * `cart_product_ids`.
	 *
	 * The old helper read the live EDD cart when neither was passed; a
	 * caller passes the cart now, which also makes a rule judged after the
	 * fact -- on a webhook, with no cart -- see the order's products.
	 *
	 * @param array $args Condition args.
	 *
	 * @return int[]
	 */
	private static function product_ids( array $args ): array {
		$ids = $args['product_ids'] ?? $args['cart_product_ids'] ?? [];
		$ids = is_array( $ids ) ? $ids : [];

		return array_values( array_filter( array_map( 'intval', array_filter( $ids, 'is_numeric' ) ) ) );
	}

	/**
	 * Compute a stable card fingerprint from gateway-provided card metadata.
	 *
	 * The result never contains a raw PAN or last4 — it is a salted hash
	 * suitable for storage and velocity comparisons.
	 *
	 * @param string      $brand     visa|mastercard|amex|...
	 * @param string      $last4     Last four digits.
	 * @param string|null $exp_month Two-digit expiry month.
	 * @param string|null $exp_year  Four-digit expiry year.
	 * @param string|null $country   ISO-2 issuing country.
	 *
	 * @return string 40-char SHA-1 hex digest.
	 */
	public static function compute_card_fingerprint(
		string $brand,
		string $last4,
		?string $exp_month = null,
		?string $exp_year = null,
		?string $country = null
	): string {
		$payload = strtolower( implode( '|', [
			trim( $brand ),
			trim( $last4 ),
			(string) $exp_month,
			(string) $exp_year,
			(string) $country,
		] ) );

		$salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : '';

		return sha1( $salt . '|' . $payload );
	}
}
