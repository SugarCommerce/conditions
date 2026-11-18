<?php
/**
 * IP Helper
 *
 * Matches an address against the patterns an admin types into an IP rule.
 *
 * @package     SugarCommerce\Conditions\Helpers
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Helpers;

/**
 * Class IP
 *
 * Exact addresses, CIDR ranges and wildcards, IPv4 and IPv6. Only what a
 * rule needs; finding the visitor's real address behind a proxy is the
 * `ip` provider's job (see Conditions::provide()).
 */
final class IP {

	/**
	 * Whether a string is an IPv4 or IPv6 address.
	 *
	 * @param string $ip The value.
	 *
	 * @return bool
	 */
	public static function is_valid( string $ip ): bool {
		return false !== filter_var( $ip, FILTER_VALIDATE_IP );
	}

	/**
	 * Whether a string is a CIDR range: `203.0.113.0/24`, `2001:db8::/32`.
	 *
	 * @param string $range The value.
	 *
	 * @return bool
	 */
	public static function is_valid_range( string $range ): bool {
		if ( ! str_contains( $range, '/' ) ) {
			return false;
		}

		[ $ip, $bits ] = explode( '/', $range, 2 );

		if ( 1 !== preg_match( '/^\d{1,3}$/', $bits ) ) {
			return false;
		}

		$bits = (int) $bits;

		if ( false !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return $bits <= 32;
		}

		if ( false !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			return $bits <= 128;
		}

		return false;
	}

	/**
	 * Whether a wildcard pattern is one that could ever be meant.
	 *
	 * Only the characters an address is written with, and at least one
	 * literal part: `*.*.*.*` matches everybody, which in an allow-list is
	 * the same as no list, so it is not accepted as a pattern.
	 *
	 * @param string $pattern The value, e.g. `203.0.113.*`.
	 *
	 * @return bool
	 */
	public static function is_valid_wildcard( string $pattern ): bool {
		$pattern = trim( $pattern );

		if ( ! str_contains( $pattern, '*' ) || 1 !== preg_match( '/^[0-9a-fA-F.:*]+$/', $pattern ) ) {
			return false;
		}

		foreach ( preg_split( '/[.:]/', $pattern ) as $part ) {
			if ( '' !== $part && '*' !== $part ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a string is a pattern an IP rule can hold.
	 *
	 * @param string $pattern An address, a CIDR range or a wildcard.
	 *
	 * @return bool
	 */
	public static function is_valid_pattern( string $pattern ): bool {
		$pattern = trim( $pattern );

		return self::is_valid( $pattern ) || self::is_valid_range( $pattern ) || self::is_valid_wildcard( $pattern );
	}

	/**
	 * Whether an address is inside a CIDR range.
	 *
	 * An address and a range of different families is a miss, not an error:
	 * mixed lists are ordinary.
	 *
	 * @param string $ip    The address.
	 * @param string $range The range.
	 *
	 * @return bool
	 */
	public static function is_in_range( string $ip, string $range ): bool {
		if ( ! self::is_valid( $ip ) || ! self::is_valid_range( $range ) ) {
			return false;
		}

		[ $subnet, $bits ] = explode( '/', $range, 2 );

		$address = inet_pton( $ip );
		$network = inet_pton( $subnet );

		if ( false === $address || false === $network || strlen( $address ) !== strlen( $network ) ) {
			return false;
		}

		$bits = (int) $bits;
		$mask = str_repeat( "\xFF", $bits >> 3 );

		if ( $bits & 7 ) {
			$mask .= chr( ( 0xFF << ( 8 - ( $bits & 7 ) ) ) & 0xFF );
		}

		$mask = str_pad( $mask, strlen( $address ), "\x00" );

		return ( $address & $mask ) === ( $network & $mask );
	}

	/**
	 * Whether an address matches a wildcard pattern.
	 *
	 * A star stands for one whole part: `203.0.113.*` is the /24,
	 * `2001:db8:*` any address whose first two groups are those.
	 *
	 * @param string $ip      The address.
	 * @param string $pattern The pattern.
	 *
	 * @return bool
	 */
	public static function matches_wildcard( string $ip, string $pattern ): bool {
		if ( ! self::is_valid( $ip ) || ! self::is_valid_wildcard( $pattern ) ) {
			return false;
		}

		$pattern = strtolower( trim( $pattern ) );

		if ( ! str_contains( $pattern, ':' ) ) {
			$regex = '/^' . str_replace( [ '.', '*' ], [ '\\.', '\d+' ], $pattern ) . '$/';

			return 1 === preg_match( $regex, $ip );
		}

		// IPv6 is compared group by group on the address written out in full,
		// because `2001:db8::1` hides the groups a pattern names. A pattern
		// shorter than eight groups covers the rest.
		if ( str_contains( $pattern, '::' ) || false === filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			return false;
		}

		$groups = array_map(
			static fn( string $group ): string => ltrim( $group, '0' ) ?: '0',
			str_split( bin2hex( (string) inet_pton( $ip ) ), 4 )
		);

		foreach ( explode( ':', $pattern ) as $i => $want ) {
			if ( ! isset( $groups[ $i ] ) ) {
				return false;
			}

			if ( '*' !== $want && ( ltrim( $want, '0' ) ?: '0' ) !== $groups[ $i ] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether an address matches any pattern in a list.
	 *
	 * Exact addresses are compared as addresses, so `2001:DB8::1` is
	 * `2001:db8:0::1`.
	 *
	 * @param string $ip       The address.
	 * @param array  $patterns Addresses, CIDR ranges and wildcards.
	 *
	 * @return bool
	 */
	public static function is_match( string $ip, array $patterns ): bool {
		$ip = trim( $ip );

		if ( ! self::is_valid( $ip ) ) {
			return false;
		}

		$binary = inet_pton( $ip );

		foreach ( $patterns as $pattern ) {
			if ( ! is_scalar( $pattern ) ) {
				continue;
			}

			$pattern = trim( (string) $pattern );

			if ( self::is_valid( $pattern ) ) {
				if ( inet_pton( $pattern ) === $binary ) {
					return true;
				}

				continue;
			}

			if ( str_contains( $pattern, '/' ) ? self::is_in_range( $ip, $pattern ) : self::matches_wildcard( $ip, $pattern ) ) {
				return true;
			}
		}

		return false;
	}
}
