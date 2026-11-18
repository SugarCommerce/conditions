<?php
/**
 * Request Built-in Conditions
 *
 * @package     SugarCommerce\Conditions\Builtin
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Builtin;

use SugarCommerce\Conditions\Helpers\Geo as GeoHelper;
use SugarCommerce\Conditions\Helpers\Parse;
use SugarCommerce\Conditions\Helpers\Request as RequestHelper;
use SugarCommerce\Conditions\Operators;
use SugarCommerce\Conditions\Options\Request as RequestOptions;

/**
 * Class Request
 *
 * Provides request/environment related conditions.
 *
 * What a browser is, which bot is asking and where a visit came from need
 * tables of user agents and referrers this library does not carry; the
 * README shows them registered from sugarcommerce/network.
 */
final class Request {

	/**
	 * Get all request conditions.
	 *
	 * @return array<string, array>
	 */
	public static function get_all(): array {
		$group = __( 'Request', 'sugarcommerce' );

		return [
			// URL
			'current_url'                    => [
				'label'         => __( 'Current URL', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'text',
				'placeholder'   => __( 'e.g. /checkout/', 'sugarcommerce' ),
				'description'   => __( 'Match against the current URL.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => RequestHelper::get_current_url( $args ),
				'required_args' => [],
			],
			'query_var'                      => [
				'label'         => __( 'Query Parameter', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'text',
				'placeholder'   => __( 'e.g. utm_source', 'sugarcommerce' ),
				'description'   => __( 'Match against a URL query parameter value.', 'sugarcommerce' ),
				'arg'           => 'query_var_value',
				'required_args' => [ 'query_var_name', 'query_var_value' ],
			],
			// Connection
			'request_method'                 => [
				'label'         => __( 'Request Method', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select methods...', 'sugarcommerce' ),
				'description'   => __( 'The HTTP request method.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => RequestOptions::get_methods(),
				'compare_value' => fn( $args ) => RequestHelper::get_method( $args ),
				'required_args' => [],
			],
			'cookie_exists'                  => [
				'label'         => __( 'Cookie Exists', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'text',
				'placeholder'   => __( 'e.g. my_cookie_name', 'sugarcommerce' ),
				'description'   => __( 'Check if a specific cookie exists.', 'sugarcommerce' ),
				'operators'     => Operators::contains(),
				'compare_value' => fn( $args, $user_value ) => RequestHelper::cookie_exists( $args, is_scalar( $user_value ) ? (string) $user_value : null ),
				'required_args' => [],
			],
			'cookie_value'                   => [
				'label'         => __( 'Cookie Value', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'text',
				'placeholder'   => __( 'cookie_name:expected_value', 'sugarcommerce' ),
				'description'   => __( 'Match against a cookie value. Format: cookie_name:value', 'sugarcommerce' ),
				'user_value'    => fn( $value ) => Parse::meta( (string) $value )['value'],
				'compare_value' => fn( $args, $user_value ) => RequestHelper::get_cookie_value( $args, is_scalar( $user_value ) ? (string) $user_value : null ),
				'required_args' => [],
			],
			'header_value'                   => [
				'label'         => __( 'HTTP Header', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'text',
				'placeholder'   => __( 'Header-Name:expected_value', 'sugarcommerce' ),
				'description'   => __( 'Match against an HTTP header value. Format: Header-Name:value', 'sugarcommerce' ),
				'user_value'    => fn( $value ) => Parse::meta( (string) $value )['value'],
				'compare_value' => fn( $args, $user_value ) => RequestHelper::get_header_value( $args, is_scalar( $user_value ) ? (string) $user_value : null ),
				'required_args' => [],
			],
			// Visitor
			'ip_address'                     => [
				'label'         => __( 'IP Address', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'ip',
				'placeholder'   => __( 'e.g. 192.168.1.0/24', 'sugarcommerce' ),
				'description'   => __( 'Match against the visitor IP address. Supports exact match, CIDR notation, and wildcards.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => RequestHelper::get_ip( $args ),
				'required_args' => [],
			],
			'country'                        => [
				'label'         => __( 'Country', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select countries...', 'sugarcommerce' ),
				'description'   => __( 'Match against the visitor country (needs a country argument, or a country provider such as a CDN header or geo-IP service).', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => GeoHelper::get_country_options(),
				'compare_value' => fn( $args ) => RequestHelper::get_country( $args ),
				'required_args' => [],
			],
			'accept_language'                => [
				'label'         => __( 'Browser Language', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select languages...', 'sugarcommerce' ),
				'description'   => __( 'Match against the languages the browser asks for. A language matches every region of it.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => RequestOptions::get_languages(),
				'compare_value' => fn( $args ) => RequestHelper::get_languages( $args ),
				'required_args' => [],
			],
			// User agent
			'user_agent'                     => [
				'label'         => __( 'User-Agent', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'text',
				'placeholder'   => __( 'e.g. Googlebot', 'sugarcommerce' ),
				'description'   => __( 'Match against the raw User-Agent header the browser or script sent.', 'sugarcommerce' ),
				'operators'     => Operators::text_advanced(),
				'compare_value' => fn( $args ) => RequestHelper::get_user_agent( $args ),
				'required_args' => [],
			],
			'user_agent_is_headless'         => [
				'label'         => __( 'Headless Browser', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'boolean',
				'description'   => __( 'True when the User-Agent contains a known automation-framework signature (HeadlessChrome, Puppeteer, Selenium, Playwright, PhantomJS, Cypress, etc). Real human checkouts almost never use a headless browser. Strong block signal — sophisticated fraudsters spoof the UA, but most don\'t bother.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => $args['user_agent_is_headless'] ?? RequestHelper::is_user_agent_headless( $args ),
				'required_args' => [],
			],
			'user_agent_is_empty'            => [
				'label'         => __( 'Empty User-Agent', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'boolean',
				'description'   => __( 'True when the User-Agent header is missing or empty. Legitimate browsers always send one — a checkout request without one is almost certainly a script. Strong block signal.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => $args['user_agent_is_empty'] ?? RequestHelper::is_user_agent_empty( $args ),
				'required_args' => [],
			],
			'user_agent_is_outdated_browser' => [
				'label'         => __( 'Outdated Browser', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'boolean',
				'description'   => __( 'True when the User-Agent advertises a browser version that\'s years out of date (any IE, Chrome <90, Firefox <78, Safari <13). Common for bots that pin to old UA strings to evade detection. Heuristic — some legit users on locked-down enterprise systems will trip this.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => $args['user_agent_is_outdated_browser'] ?? RequestHelper::is_user_agent_outdated_browser( $args ),
				'required_args' => [],
			],
			// Referrer
			'referrer_url'                   => [
				'label'         => __( 'Referrer URL', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'text',
				'placeholder'   => __( 'e.g. example.com/page', 'sugarcommerce' ),
				'description'   => __( 'Match against the full HTTP referrer URL.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => RequestHelper::get_referrer( $args ),
				'required_args' => [],
			],
			'referrer_domain'                => [
				'label'         => __( 'Referrer Domain', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'text',
				'placeholder'   => __( 'e.g. google.com', 'sugarcommerce' ),
				'description'   => __( 'Match against the referrer root domain.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => RequestHelper::get_referrer_domain( $args ),
				'required_args' => [],
			],
			'has_referrer'                   => [
				'label'         => __( 'Referrer Present', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'boolean',
				'description'   => __( 'Check if the visitor has any referrer (not direct traffic).', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => RequestHelper::has_referrer( $args ),
				'required_args' => [],
			],
			'is_external_referrer'           => [
				'label'         => __( 'External Referrer', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'boolean',
				'description'   => __( 'Check if the visitor came from an external website.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => RequestHelper::is_external_referrer( $args ),
				'required_args' => [],
			],
			// UTM
			'utm_parameter'                  => [
				'label'         => __( 'UTM Parameter', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'text_unit',
				'placeholder'   => __( 'e.g. newsletter, google, spring_sale', 'sugarcommerce' ),
				'description'   => __( 'Match against a UTM parameter on the page the visitor landed on.', 'sugarcommerce' ),
				'units'         => fn() => RequestOptions::get_utm_parameters(),
				'compare_value' => fn( $args ) => RequestHelper::get_utm_parameter( $args ),
				'required_args' => [],
			],
		];
	}
}
