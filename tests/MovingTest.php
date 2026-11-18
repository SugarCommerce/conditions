<?php
/**
 * The README's "Moving from arraypress/wp-conditions" table.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Tests;

use SugarCommerce\Conditions\Builtin;
use SugarCommerce\Conditions\Conditions;
use SugarCommerce\Conditions\Helpers\Velocity;
use PHPUnit\Framework\TestCase;

/**
 * Class MovingTest
 *
 * What a plugin switching over relies on: the globals are gone, every
 * built-in condition it saved rules against is still built in under the
 * same id (so saved rules keep working), and the ones that are not are the
 * ones the README shows registered from elsewhere.
 */
final class MovingTest extends TestCase {

	/**
	 * Every condition id wp-conditions built in, outside EDD, WooCommerce
	 * and the IP lookup services.
	 *
	 * @var string[]
	 */
	private const OLD_CORE = [
		'is_front_page', 'is_home', 'is_singular', 'is_single', 'is_page', 'is_attachment', 'is_search', 'is_404', 'is_preview',
		'is_paged', 'is_archive', 'is_post_type_archive', 'is_category', 'is_tag', 'is_tax', 'is_author', 'is_date', 'is_year',
		'is_month', 'is_day', 'is_admin', 'is_network_admin', 'is_ajax', 'is_rest', 'is_cron', 'is_cli', 'is_feed',
		'is_customizer_preview', 'is_embed', 'is_ssl', 'is_multisite', 'is_main_site', 'is_debug', 'environment', 'is_local',
		'is_rtl', 'current_date', 'current_time', 'current_year', 'current_month', 'quarter', 'week_of_year', 'day_of_week',
		'day_of_month', 'day_of_year', 'time_of_day', 'is_weekend', 'is_weekday', 'is_business_hours', 'email_is_freemail',
		'email_domain', 'email_has_plus_alias', 'email_local_suspicion_score', 'email_tld', 'email_local_length',
		'email_local_digit_density', 'geo_ip_matches_billing_country', 'geo_card_matches_billing_country',
		'geo_billing_country_in_list', 'geo_ip_country_in_list', 'geo_distance_km', 'card_funding_type', 'card_brand',
		'stripe_risk_level', 'stripe_risk_score', 'square_risk_level', 'square_avs_status', 'square_cvv_status',
		'velocity_orders_by_card_fingerprint', 'velocity_distinct_emails_by_card_fingerprint', 'paypal_seller_protection',
		'paypal_avs_code', 'paypal_cvv_code', 'paypal_payer_country', 'paypal_payer_country_matches_billing',
		'paypal_decline_reason', 'post_title', 'post_slug', 'post_status', 'post_type', 'post_author', 'post_parent',
		'post_template', 'post_format', 'post_password_protected', 'post_menu_order', 'post_date_created', 'post_date_modified',
		'post_age', 'post_modified_age', 'post_category', 'post_tag', 'has_term', 'has_featured_image', 'post_comment_count',
		'post_comment_status', 'is_sticky', 'has_excerpt', 'post_word_count', 'has_shortcode', 'has_block', 'post_meta_text',
		'post_meta_number', 'current_url', 'query_var', 'request_method', 'cookie_exists', 'cookie_value', 'header_value',
		'ip_address', 'country', 'accept_language', 'device_type', 'browser', 'operating_system', 'is_bot', 'is_ai_bot',
		'is_ai_user_agent', 'is_link_preview', 'in_app_browser', 'user_agent_is_headless', 'user_agent_is_empty',
		'user_agent_is_outdated_browser', 'referrer_url', 'referrer_domain', 'traffic_source', 'search_engine',
		'social_platform', 'search_terms', 'has_referrer', 'is_external_referrer', 'utm_parameter', 'is_logged_in', 'user_id',
		'user_role', 'has_capability', 'user_email', 'user_username', 'user_display_name', 'user_first_name', 'user_last_name',
		'user_nicename', 'user_website', 'user_locale', 'is_super_admin', 'user_date_registered', 'user_registered',
		'user_post_count', 'user_comment_count', 'user_meta_text', 'user_meta_number', 'velocity_orders_by_ip',
		'velocity_orders_by_email', 'velocity_same_product_orders_by_ip', 'velocity_same_product_orders_by_email',
		'velocity_distinct_emails_by_ip', 'velocity_distinct_ips_by_email', 'velocity_failed_orders_by_ip',
		'velocity_failed_orders_by_email', 'velocity_blocked_attempts_by_ip', 'velocity_blocked_attempts_by_email',
	];

	/**
	 * The ones that read a user-agent or referrer table, which the README
	 * registers from sugarcommerce/network.
	 *
	 * @var string[]
	 */
	private const REGISTERED_ELSEWHERE = [
		'device_type', 'browser', 'operating_system', 'is_bot', 'is_ai_bot', 'is_ai_user_agent', 'is_link_preview',
		'in_app_browser', 'traffic_source', 'search_engine', 'social_platform', 'search_terms',
	];

	protected function setUp(): void {
		Conditions::reset();
		wpc_reset();
	}

	/**
	 * No global helper is declared.
	 *
	 * A global function is the one thing Strauss cannot prefix, so two
	 * plugins bundling different copies shared whichever loaded first.
	 */
	public function test_no_global_helper_is_declared(): void {
		foreach ( [ 'register_conditions', 'register_condition', 'check_conditions', 'check_all_conditions' ] as $function ) {
			$this->assertFalse( function_exists( $function ), sprintf( '%s() is declared.', $function ) );
		}

		$composer = json_decode( (string) file_get_contents( dirname( __DIR__ ) . '/composer.json' ), true );
		$this->assertArrayNotHasKey( 'files', $composer['autoload'], 'A file of functions is autoloaded.' );
		$this->assertSame( [ 'php' ], array_keys( $composer['require'] ), 'The library depends on something.' );

		// Standalone: no class from another SugarCommerce package.
		foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( dirname( __DIR__ ) . '/src', \FilesystemIterator::SKIP_DOTS ) ) as $file ) {
			$this->assertDoesNotMatchRegularExpression( '/SugarCommerce\\\\(?!Conditions\\b)/', (string) file_get_contents( (string) $file ), $file . ' uses another SugarCommerce package.' );
		}
	}

	/**
	 * Every old built-in id is still built in, so saved rules keep working,
	 * except the ones the README registers from elsewhere.
	 */
	public function test_saved_rules_keep_their_conditions(): void {
		$now = Builtin::get_all();

		foreach ( self::OLD_CORE as $id ) {
			if ( in_array( $id, self::REGISTERED_ELSEWHERE, true ) ) {
				$this->assertArrayNotHasKey( $id, $now );
				continue;
			}

			$this->assertArrayHasKey( $id, $now, "$id was lost." );
		}

		$readme = (string) file_get_contents( dirname( __DIR__ ) . '/README.md' );

		foreach ( self::REGISTERED_ELSEWHERE as $id ) {
			$this->assertStringContainsString( "'$id'", $readme, "The README does not show $id registered." );
		}
	}

	/**
	 * The old velocity counters are one call now.
	 */
	public function test_the_velocity_counters_are_one_call(): void {
		$args = [
			'ip'                    => '203.0.113.1',
			'velocity_orders_by_ip' => 3,
		];

		$this->assertSame( 3, Velocity::count( 'orders_by_ip', $args ) );
		$this->assertSame( 'INTERVAL 2 DAY', Velocity::to_interval( 2, 'day' ) );
		$this->assertSame( 'INTERVAL 1 HOUR', Velocity::to_interval( 0, 'fortnight' ) );
	}
}
