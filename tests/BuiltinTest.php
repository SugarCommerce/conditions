<?php
/**
 * The built-in conditions: well-formed, standalone, and judged as described.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Tests;

use SugarCommerce\Conditions\Builtin;
use SugarCommerce\Conditions\Conditions;
use SugarCommerce\Conditions\Helpers\Geo;
use SugarCommerce\Conditions\Operators;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class BuiltinTest
 */
final class BuiltinTest extends TestCase {

	/**
	 * The field types the editor can draw.
	 *
	 * @var string[]
	 */
	private const TYPES = [ 'text', 'number', 'number_unit', 'text_unit', 'boolean', 'date', 'time', 'ip', 'email', 'tags', 'select', 'post', 'term', 'user', 'ajax' ];

	protected function setUp(): void {
		Conditions::reset();
		wpc_reset();
	}

	protected function tearDown(): void {
		Conditions::reset();
		wpc_reset();
	}

	/**
	 * Judge one rule against a built-in condition.
	 *
	 * @param string $condition The built-in id.
	 * @param string $operator  The operator.
	 * @param mixed  $value     The rule's value.
	 * @param array  $args      The context.
	 *
	 * @return bool
	 */
	private function judge( string $condition, string $operator, mixed $value, array $args ): bool {
		Conditions::add( 'judge_set', $condition );

		return Conditions::matches( 'judge_set', [
			[
				'rules' => [
					[
						'condition' => $condition,
						'operator'  => $operator,
						'value'     => $value,
					],
				],
			],
		], $args );
	}

	/**
	 * Every built-in condition has what the editor and the matcher need.
	 */
	public function test_every_condition_is_well_formed(): void {
		$all = Builtin::get_all();

		$this->assertGreaterThanOrEqual( 150, count( $all ) );

		foreach ( $all as $id => $condition ) {
			$this->assertNotEmpty( $condition['label'] ?? '', "$id has no label." );
			$this->assertNotEmpty( $condition['group'] ?? '', "$id has no group." );
			$this->assertContains( $condition['type'] ?? '', self::TYPES, "$id has an unknown type." );
			$this->assertTrue(
				is_callable( $condition['compare_value'] ?? null ) || isset( $condition['arg'] ),
				"$id has nothing to compare."
			);
			$this->assertIsArray( $condition['required_args'] ?? [], "$id's required arguments are not a list." );

			$operators = $condition['operators'] ?? Operators::for_type( $condition['type'], (bool) ( $condition['multiple'] ?? false ) );
			$this->assertNotEmpty( $operators, "$id has no operators." );
		}
	}

	/**
	 * No two packs use the same id: array_merge would let the later one win
	 * without a word.
	 */
	public function test_no_two_packs_share_an_id(): void {
		$packs = ( new ReflectionClass( Builtin::class ) )->getConstant( 'PACKS' );
		$total = 0;

		foreach ( $packs as $pack ) {
			$total += count( $pack::get_all() );
		}

		$this->assertSame( $total, count( Builtin::get_all() ) );
	}

	/**
	 * Nothing about a particular shop or service is built in.
	 */
	public function test_nothing_shop_or_service_specific_is_built_in(): void {
		foreach ( array_keys( Builtin::get_all() ) as $id ) {
			$this->assertDoesNotMatchRegularExpression( '/^(edd|wc|woo|proxycheck|ipqs|ipinfo|abuseipdb|cart_|order_|product_|customer_)/', $id );
		}

		$this->assertNull( Builtin::get( 'browser' ), 'A condition needing a user-agent table is built in.' );
		$this->assertNotNull( Builtin::get( 'user_role' ) );
	}

	/**
	 * The geo conditions.
	 */
	public function test_geo(): void {
		$this->assertTrue( $this->judge( 'geo_ip_matches_billing_country', 'no', null, [ 'ip_country' => 'US', 'billing_country' => 'gb' ] ) );
		$this->assertTrue( $this->judge( 'geo_ip_matches_billing_country', 'yes', null, [ 'ip_country' => 'gb', 'billing_country' => 'GB ' ] ) );
		$this->assertFalse( $this->judge( 'geo_ip_matches_billing_country', 'no', null, [ 'billing_country' => 'GB' ] ), 'Judged without the IP country.' );
		$this->assertFalse( $this->judge( 'geo_card_matches_billing_country', 'yes', null, [ 'card_country' => '', 'billing_country' => ' ' ] ), 'Two unknown countries matched.' );
		$this->assertTrue( $this->judge( 'geo_billing_country_in_list', 'any', [ 'GB', 'IE' ], [ 'billing_country' => 'ie' ] ) );
		$this->assertTrue( $this->judge( 'geo_ip_country_in_list', 'none', [ 'RU' ], [ 'ip_country' => 'GB' ] ) );
		$this->assertTrue( $this->judge( 'geo_distance_km', '>=', 5000, [ 'geo_distance_km' => Geo::haversine( 51.5, -0.12, 40.71, -74.0 ) ] ) );
		$this->assertEqualsWithDelta( 5570, Geo::haversine( 51.5, -0.12, 40.71, -74.0 ), 10 );
	}

	/**
	 * Country names come from the provider, ext-intl or the codes.
	 */
	public function test_country_options_need_no_library(): void {
		$options = Geo::get_country_options();
		$codes   = array_column( $options, 'value' );

		$this->assertCount( 250, $options, 'ISO 3166-1, and Kosovo.' );
		$this->assertContains( 'GB', $codes );
		$this->assertContains( 'XK', $codes );

		Conditions::provide( 'countries', static fn(): array => [ 'gb' => 'United Kingdom', 'IE' => 'Ireland' ] );

		$this->assertSame(
			[
				[
					'value' => 'GB',
					'label' => 'United Kingdom',
				],
				[
					'value' => 'IE',
					'label' => 'Ireland',
				],
			],
			Geo::get_country_options()
		);
	}

	/**
	 * The email conditions.
	 */
	public function test_email(): void {
		$this->assertTrue( $this->judge( 'email_is_freemail', 'yes', null, [ 'email' => 'jo@gmail.com' ] ) );
		$this->assertTrue( $this->judge( 'email_domain', 'any_exact', [ 'mailinator.com' ], [ 'email' => 'x@Mailinator.com' ] ) );
		$this->assertTrue( $this->judge( 'email_tld', 'any_exact', [ 'xyz' ], [ 'email' => 'x@shop.xyz' ] ) );
		$this->assertTrue( $this->judge( 'email_local_digit_density', '>=', 50, [ 'email' => 'ab12@example.com' ] ) );
		$this->assertFalse( $this->judge( 'email_has_plus_alias', 'yes', null, [] ), 'Judged without an email.' );
	}

	/**
	 * The request conditions, from arguments.
	 */
	public function test_request(): void {
		$this->assertTrue( $this->judge( 'ip_address', 'ip_match', [ '203.0.113.0/24' ], [ 'ip_address' => '203.0.113.9' ] ) );
		$this->assertTrue( $this->judge( 'country', 'any', [ 'GB' ], [ 'country' => 'gb' ] ) );
		$this->assertTrue( $this->judge( 'accept_language', 'any', [ 'en' ], [ 'accept_language' => 'en-GB,fr;q=0.5' ] ) );
		$this->assertTrue( $this->judge( 'user_agent_is_headless', 'yes', null, [ 'user_agent' => 'Mozilla/5.0 HeadlessChrome/120' ] ) );
		$this->assertTrue( $this->judge( 'user_agent', 'contains', 'curl', [ 'user_agent' => 'curl/8.4' ] ) );
		$this->assertTrue( $this->judge( 'request_method', 'any', [ 'POST' ], [ 'request_method' => 'post' ] ) );
		$this->assertTrue( $this->judge( 'referrer_domain', '==', 'bbc.co.uk', [ 'referrer_url' => 'https://www.news.bbc.co.uk/a' ] ) );
		// A UTM rule without its unit asks about utm_source, not the campaign.
		$this->assertFalse( $this->judge( 'utm_parameter', '==', 'spring', [ 'utm_campaign' => 'spring' ] ) );
		$this->assertTrue(
			Conditions::matches( 'judge_set', [
				[
					'rules' => [
						[
							'condition' => 'utm_parameter',
							'operator'  => '==',
							'value'     => [
								'text' => 'spring',
								'unit' => 'campaign',
							],
						],
					],
				],
			], [ 'utm_campaign' => 'spring' ] )
		);
	}

	/**
	 * The payment conditions read what the gateway said.
	 */
	public function test_payment(): void {
		$this->assertTrue( $this->judge( 'card_funding_type', 'any', [ 'prepaid' ], [ 'card_funding' => 'Prepaid' ] ) );
		$this->assertTrue( $this->judge( 'stripe_risk_score', '>=', 65, [ 'stripe_risk_score' => 70 ] ) );
		$this->assertTrue( $this->judge( 'paypal_payer_country_matches_billing', 'no', null, [ 'paypal_payer_country' => 'US', 'billing_country' => 'GB' ] ) );
		$this->assertTrue( $this->judge( 'paypal_decline_reason', 'any_exact', [ 'DECLINED_BY_RISK_FRAUD_FILTERS' ], [ 'paypal_decline_reason' => 'declined_by_risk_fraud_filters' ] ) );
	}

	/**
	 * A velocity rule is judged with the shop's counter, and not without one.
	 */
	public function test_velocity(): void {
		$rule = [
			'number' => 3,
			'unit'   => '10_minutes',
		];

		$this->assertFalse( $this->judge( 'velocity_orders_by_ip', '>=', $rule, [ 'ip' => '203.0.113.9' ] ), 'Matched with nothing counting.' );
		$this->assertFalse( $this->judge( 'velocity_orders_by_ip', '<', $rule, [ 'ip' => '203.0.113.9' ] ), 'No count read as nought.' );

		add_filter( Conditions::hook( 'velocity_orders_by_ip' ), static fn( $count, $ip, $number, $unit ) => 10 === $number && 'minute' === $unit ? 4 : 0, 10, 4 );

		$this->assertTrue( $this->judge( 'velocity_orders_by_ip', '>=', $rule, [ 'ip' => '203.0.113.9' ] ) );
	}

	/**
	 * The date and time conditions read an argument before the clock.
	 */
	public function test_date_and_time(): void {
		$this->assertTrue( $this->judge( 'day_of_week', 'any', [ '6', '7' ], [ 'day_of_week' => 7 ] ) );
		$this->assertTrue( $this->judge( 'current_date', '>=', '2026-01-01', [ 'current_date' => '2026-11-18' ] ) );
		$this->assertTrue( $this->judge( 'is_weekend', 'no', null, [ 'is_weekend' => false ] ) );
	}
}
