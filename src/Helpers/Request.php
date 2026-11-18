<?php
/**
 * Request Helper
 *
 * Provides utilities for retrieving request data from condition arguments.
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
 * Class Request
 *
 * Every getter takes the condition arguments first: a value passed in wins,
 * so a rule can be judged for a request that is not this one -- an order
 * placed earlier, a webhook, a test.
 */
final class Request {

	/**
	 * Two-label endings where the registrable domain is three labels.
	 *
	 * @var string[]
	 */
	private const COMPOUND_SUFFIXES = [
		'co.uk',
		'org.uk',
		'me.uk',
		'ac.uk',
		'gov.uk',
		'net.uk',
		'sch.uk',
		'com.au',
		'net.au',
		'org.au',
		'edu.au',
		'gov.au',
		'co.nz',
		'net.nz',
		'org.nz',
		'govt.nz',
		'co.za',
		'org.za',
		'co.jp',
		'ne.jp',
		'or.jp',
		'ac.jp',
		'go.jp',
		'co.in',
		'net.in',
		'org.in',
		'gov.in',
		'ac.in',
		'com.br',
		'net.br',
		'org.br',
		'gov.br',
		'com.mx',
		'org.mx',
		'com.ar',
		'com.tr',
		'gov.tr',
		'com.sa',
		'com.sg',
		'com.hk',
		'com.tw',
		'com.cn',
		'net.cn',
		'org.cn',
		'gov.cn',
		'co.kr',
		'or.kr',
		'com.pl',
		'com.es',
		'com.co',
		'co.il',
		'com.my',
		'com.ph',
		'co.th',
		'com.vn',
	];

	/**
	 * The UTM parameters a rule can ask about.
	 *
	 * @var string[]
	 */
	public const UTM_PARAMETERS = [ 'source', 'medium', 'campaign', 'term', 'content' ];

	/**
	 * Read a request value, unslashed and cleaned of control characters.
	 *
	 * WordPress adds slashes to every superglobal on the way in, so a raw read
	 * returns O\'Brien where the browser sent O'Brien. That matters more here
	 * than in most places: these values are compared against what an admin
	 * typed into a rule, and a stray backslash is the difference between a rule
	 * matching and not.
	 *
	 * @param array  $source The superglobal to read.
	 * @param string $key    Key to read.
	 *
	 * @return string
	 */
	private static function read( array $source, string $key ): string {
		if ( ! isset( $source[ $key ] ) || ! is_scalar( $source[ $key ] ) ) {
			return '';
		}

		// Not sanitize_text_field(): it deletes every %xx sequence, so a
		// URL-encoded cookie or header -- which is most of them -- never
		// equalled what the admin typed. Control characters are the only thing
		// a comparison has no use for.
		$value = wp_check_invalid_utf8( wp_unslash( (string) $source[ $key ] ) );

		return trim( (string) preg_replace( '/[\x00-\x1F\x7F]/', '', $value ) );
	}

	/** The visitor ***************************************************************/

	/**
	 * The visitor's IP: the `ip_address` argument, the `ip` provider, or
	 * REMOTE_ADDR.
	 *
	 * Behind a proxy REMOTE_ADDR is the proxy's address, and the visitor's
	 * arrives in a header any client could also send -- so this library does
	 * not read forwarding headers at all. A plugin that knows which proxy the
	 * site is behind provides `ip` (see Conditions::provide()).
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string|null
	 */
	public static function get_ip( array $args = [] ): ?string {
		if ( isset( $args['ip_address'] ) && is_scalar( $args['ip_address'] ) ) {
			return (string) $args['ip_address'];
		}

		if ( Conditions::has_provider( 'ip' ) ) {
			$ip = Conditions::provided( 'ip' );

			return is_scalar( $ip ) && '' !== (string) $ip ? (string) $ip : null;
		}

		$remote = self::read( $_SERVER, 'REMOTE_ADDR' );

		return IP::is_valid( $remote ) ? $remote : null;
	}

	/**
	 * The visitor's country code: the `country` argument or the `country`
	 * provider.
	 *
	 * A CDN's country header is the client's to write unless the CDN wrote
	 * it, so it is not read here; a provider that checks who connected does.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string|null Two-letter code, or null when nothing trustworthy says.
	 */
	public static function get_country( array $args = [] ): ?string {
		$code = $args['country'] ?? Conditions::provided( 'country' );

		if ( ! is_scalar( $code ) ) {
			return null;
		}

		$code = strtoupper( trim( (string) $code ) );

		// XX is Cloudflare's unknown and T1 its Tor marker; neither is a country.
		return 1 === preg_match( '/^[A-Z]{2}$/', $code ) && ! in_array( $code, [ 'XX', 'T1' ], true ) ? $code : null;
	}

	/**
	 * The browser's languages, best first: every tag and its language.
	 *
	 * `en-GB,en;q=0.8,fr;q=0.5` reads as `en-GB`, `en`, `fr` -- so a rule for
	 * English matches a British browser, and a rule for `en-GB` still can.
	 * The old reading kept only the first tag as sent, and "English" never
	 * matched anybody whose browser said where they were.
	 *
	 * @param array $args The condition arguments. `accept_language` overrides
	 *                    the header, as a tag or a list of them.
	 *
	 * @return string[]
	 */
	public static function get_languages( array $args = [] ): array {
		$header = $args['accept_language'] ?? self::read( $_SERVER, 'HTTP_ACCEPT_LANGUAGE' );
		$header = is_array( $header ) ? implode( ',', array_filter( $header, 'is_scalar' ) ) : (string) $header;
		$tags   = [];

		foreach ( explode( ',', $header ) as $i => $part ) {
			$bits = explode( ';', trim( $part ) );
			$tag  = trim( $bits[0] );

			if ( 1 !== preg_match( '/^[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})*$/', $tag ) ) {
				continue;
			}

			$q = 1.0;

			foreach ( array_slice( $bits, 1 ) as $param ) {
				if ( preg_match( '/^\s*q\s*=\s*([0-9.]+)\s*$/i', $param, $m ) ) {
					$q = (float) $m[1];
				}
			}

			if ( $q <= 0 ) {
				continue;
			}

			$parts = explode( '-', $tag );
			$tag   = strtolower( $parts[0] ) . ( isset( $parts[1] ) ? '-' . strtoupper( $parts[1] ) : '' );

			// Order by quality, then by position.
			$tags[] = [ $q, -$i, $tag ];
		}

		rsort( $tags );

		$out = [];

		foreach ( $tags as [ , , $tag ] ) {
			$out[] = $tag;
			$out[] = explode( '-', $tag )[0];
		}

		return array_values( array_unique( $out ) );
	}

	/** The request ***************************************************************/

	/**
	 * Get current URL.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string
	 */
	public static function get_current_url( array $args = [] ): string {
		if ( isset( $args['current_url'] ) ) {
			return (string) $args['current_url'];
		}

		$protocol = is_ssl() ? 'https://' : 'http://';
		$host     = sanitize_text_field( self::read( $_SERVER, 'HTTP_HOST' ) );

		// esc_url_raw rather than sanitize_text_field: the URI carries query
		// separators and encoded characters the text sniff would strip, and a
		// truncated URL silently changes what a URL rule matches.
		$uri = isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] )
			? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';

		return $protocol . $host . $uri;
	}

	/**
	 * Get the HTTP request method.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string
	 */
	public static function get_method( array $args = [] ): string {
		return strtoupper( (string) ( $args['request_method'] ?? ( self::read( $_SERVER, 'REQUEST_METHOD' ) ?: 'GET' ) ) );
	}

	/**
	 * Check if a cookie exists.
	 *
	 * @param array       $args       The condition arguments.
	 * @param string|null $user_value The cookie name to check.
	 *
	 * @return string The cookie name if it exists, empty string otherwise.
	 */
	public static function cookie_exists( array $args, ?string $user_value ): string {
		if ( null === $user_value || '' === $user_value ) {
			return '';
		}

		$cookies = isset( $args['cookies'] ) && is_array( $args['cookies'] ) ? $args['cookies'] : $_COOKIE;

		return isset( $cookies[ $user_value ] ) ? $user_value : '';
	}

	/**
	 * Get a cookie value.
	 *
	 * @param array       $args       The condition arguments.
	 * @param string|null $user_value The input in format "cookie_name:expected_value".
	 *
	 * @return string The cookie value or empty string.
	 */
	public static function get_cookie_value( array $args, ?string $user_value ): string {
		if ( null === $user_value || ! str_contains( $user_value, ':' ) ) {
			return '';
		}

		$cookies = isset( $args['cookies'] ) && is_array( $args['cookies'] ) ? $args['cookies'] : $_COOKIE;

		return self::read( $cookies, Parse::meta( $user_value )['key'] );
	}

	/**
	 * Get an HTTP header value.
	 *
	 * @param array       $args       The condition arguments.
	 * @param string|null $user_value The input in format "Header-Name:expected_value".
	 *
	 * @return string The header value or empty string.
	 */
	public static function get_header_value( array $args, ?string $user_value ): string {
		if ( null === $user_value || ! str_contains( $user_value, ':' ) ) {
			return '';
		}

		$name = strtolower( Parse::meta( $user_value )['key'] );

		// Content-Type and Content-Length are the two without the HTTP_ prefix.
		$server_key = match ( $name ) {
			'content-type'   => 'CONTENT_TYPE',
			'content-length' => 'CONTENT_LENGTH',
			default          => 'HTTP_' . strtoupper( str_replace( '-', '_', $name ) ),
		};

		return self::read( $_SERVER, $server_key );
	}

	/**
	 * A UTM parameter on the landing URL.
	 *
	 * Read from this request's query string. The old helper read the
	 * referrer's, which is the page the visitor left -- a campaign link
	 * carries its parameters on the page it lands on.
	 *
	 * @param array $args The condition arguments: the rule's unit names the
	 *                    parameter, and `utm_{name}` overrides the query.
	 *
	 * @return string The value, or ''.
	 */
	public static function get_utm_parameter( array $args ): string {
		$name = strtolower( (string) Parse::text_unit( $args, 'source' )['unit'] );

		if ( ! in_array( $name, self::UTM_PARAMETERS, true ) ) {
			return '';
		}

		if ( isset( $args[ 'utm_' . $name ] ) && is_scalar( $args[ 'utm_' . $name ] ) ) {
			return (string) $args[ 'utm_' . $name ];
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read only, compared with a rule.
		return self::read( $_GET, 'utm_' . $name );
	}

	/** The referrer **************************************************************/

	/**
	 * The referrer URL, when there is one and it is a URL.
	 *
	 * @param array $args The condition arguments. `referrer_url` overrides.
	 *
	 * @return string
	 */
	public static function get_referrer( array $args = [] ): string {
		if ( isset( $args['referrer_url'] ) && is_scalar( $args['referrer_url'] ) ) {
			return (string) $args['referrer_url'];
		}

		if ( ! isset( $_SERVER['HTTP_REFERER'] ) || ! is_string( $_SERVER['HTTP_REFERER'] ) ) {
			return '';
		}

		// esc_url_raw drops schemes that are not http(s) -- a javascript: URL
		// is not a referrer anyone should compare against.
		$url = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );

		return false !== filter_var( $url, FILTER_VALIDATE_URL ) ? $url : '';
	}

	/**
	 * The referrer's host, lower-cased.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string
	 */
	public static function get_referrer_host( array $args = [] ): string {
		$host = wp_parse_url( self::get_referrer( $args ), PHP_URL_HOST );

		return is_string( $host ) ? strtolower( $host ) : '';
	}

	/**
	 * The referrer's registrable domain: `news.bbc.co.uk` is `bbc.co.uk`.
	 *
	 * @param array $args The condition arguments. `referrer_domain` overrides.
	 *
	 * @return string
	 */
	public static function get_referrer_domain( array $args = [] ): string {
		if ( isset( $args['referrer_domain'] ) && is_scalar( $args['referrer_domain'] ) ) {
			return (string) $args['referrer_domain'];
		}

		return self::root_domain( self::get_referrer_host( $args ) );
	}

	/**
	 * Whether there is a referrer at all.
	 *
	 * @param array $args The condition arguments. `has_referrer` overrides.
	 *
	 * @return bool
	 */
	public static function has_referrer( array $args = [] ): bool {
		return (bool) ( $args['has_referrer'] ?? ( '' !== self::get_referrer( $args ) ) );
	}

	/**
	 * Whether the referrer is another site.
	 *
	 * @param array $args The condition arguments. `is_external_referrer` overrides.
	 *
	 * @return bool
	 */
	public static function is_external_referrer( array $args = [] ): bool {
		if ( isset( $args['is_external_referrer'] ) ) {
			return (bool) $args['is_external_referrer'];
		}

		$host = self::get_referrer_host( $args );
		$site = wp_parse_url( (string) get_option( 'siteurl' ), PHP_URL_HOST );

		return '' !== $host && $host !== strtolower( (string) $site );
	}

	/**
	 * A host's registrable domain, without `www.`.
	 *
	 * Taking the last two labels gives `co.uk` for every site in Britain; where
	 * the last two are a known compound ending, take three.
	 *
	 * @param string $host The host.
	 *
	 * @return string
	 */
	public static function root_domain( string $host ): string {
		$host  = (string) preg_replace( '/^www\./', '', strtolower( trim( $host, '. ' ) ) );
		$parts = explode( '.', $host );
		$count = count( $parts );

		if ( $count < 3 ) {
			return $host;
		}

		$suffix = $parts[ $count - 2 ] . '.' . $parts[ $count - 1 ];

		return in_array( $suffix, self::COMPOUND_SUFFIXES, true )
			? $parts[ $count - 3 ] . '.' . $suffix
			: $suffix;
	}

	/** The user agent ************************************************************/

	/**
	 * Get the raw user-agent string.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string
	 */
	public static function get_user_agent( array $args = [] ): string {
		if ( isset( $args['user_agent'] ) ) {
			return (string) $args['user_agent'];
		}

		return self::read( $_SERVER, 'HTTP_USER_AGENT' );
	}

	/**
	 * Whether the user-agent header is missing or empty.
	 *
	 * Legitimate browsers always send one. Empty UAs are usually scripts.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return bool
	 */
	public static function is_user_agent_empty( array $args = [] ): bool {
		return '' === trim( self::get_user_agent( $args ) );
	}

	/**
	 * Whether the user-agent looks like a headless browser or automation tool.
	 *
	 * Detects HeadlessChrome, PhantomJS, Selenium, Puppeteer, Playwright,
	 * Cypress, Nightmare, and similar automation framework signatures.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return bool
	 */
	public static function is_user_agent_headless( array $args = [] ): bool {
		$ua = self::get_user_agent( $args );

		if ( '' === $ua ) {
			return false;
		}

		$signatures = [
			'HeadlessChrome',
			'PhantomJS',
			'SlimerJS',
			'Selenium',
			'WebDriver',
			'puppeteer',
			'Playwright',
			'Cypress',
			'Nightmare',
			'jsdom',
			'Lighthouse',
		];

		foreach ( $signatures as $sig ) {
			if ( false !== stripos( $ua, $sig ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the user-agent advertises an outdated browser version.
	 *
	 * Heuristic only — version thresholds were chosen to flag genuinely
	 * abandoned releases without snagging modern enterprise builds.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return bool
	 */
	public static function is_user_agent_outdated_browser( array $args = [] ): bool {
		$ua = self::get_user_agent( $args );

		if ( '' === $ua ) {
			return false;
		}

		// Internet Explorer (any version is now outdated).
		if ( preg_match( '/MSIE \d|Trident\//i', $ua ) ) {
			return true;
		}

		// Chrome < 90. Edge and other Chromium browsers advertise Chrome too --
		// only match when there is no Edg/OPR/YaBrowser token alongside.
		if ( preg_match( '/Chrome\/(\d+)/i', $ua, $m ) && (int) $m[1] < 90
			&& ! preg_match( '/Edg(e|A|iOS)?\/|OPR\/|YaBrowser\//i', $ua ) ) {
			return true;
		}

		// Firefox < 78.
		if ( preg_match( '/Firefox\/(\d+)/i', $ua, $m ) && (int) $m[1] < 78 ) {
			return true;
		}

		// Safari < 13 (read the WebKit-style version token). Not on Android
		// or alongside a Chrome token: an Android WebView and every in-app
		// browser advertise "Version/4.0 ... Chrome/124 ... Safari", which is
		// not an old Safari, it is a current Chrome.
		return ! preg_match( '/Chrome\/|CriOS\/|Android/i', $ua )
			&& preg_match( '/Version\/(\d+).*Safari/i', $ua, $m ) && (int) $m[1] < 13;
	}
}
