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
use SugarCommerce\Conditions\Traits\Helpers\Request\Referrer;
use SugarCommerce\Conditions\Traits\Helpers\Request\UserAgent;
use SugarCommerce\Network\AcceptLanguage;
use SugarCommerce\Network\IP;

/**
 * Class Request
 *
 * Every getter takes the condition arguments first: a value passed in wins,
 * so a rule can be judged for a request that is not this one -- an order
 * placed earlier, a webhook, a test.
 */
final class Request {

	use Referrer;
	use UserAgent;

	/**
	 * The UTM parameters a rule can ask about.
	 *
	 * @var string[]
	 */
	public const array UTM_PARAMETERS = [ 'source', 'medium', 'campaign', 'term', 'content' ];

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
		$header = $args['accept_language'] ?? null;
		$header = is_array( $header ) ? implode( ',', array_filter( $header, 'is_scalar' ) ) : ( null === $header ? null : (string) $header );
		$out    = [];

		// network's reading: capped, qualities clamped, `q=0` dropped, best
		// first and in the order sent among equals. `*` is any language,
		// which no rule names.
		foreach ( array_keys( AcceptLanguage::parse( $header ) ) as $tag ) {
			if ( '*' === $tag ) {
				continue;
			}

			$out[] = (string) $tag;
			$out[] = AcceptLanguage::extract_language( (string) $tag );
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

	/** The user agent ************************************************************/
}
