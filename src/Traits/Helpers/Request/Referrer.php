<?php
/**
 * The Referrer
 *
 * @package SugarCommerce\Conditions\Helpers
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Traits\Helpers\Request;

/**
 * Where a visitor came from: the referrer, its host and domain, and
 * whether it was another site.
 */
trait Referrer {

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
}
