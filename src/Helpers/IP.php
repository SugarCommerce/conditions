<?php
/**
 * IP Helper
 *
 * The addresses and ranges an IP rule holds, read here so the library needs
 * nothing but PHP and WordPress.
 *
 * @package SugarCommerce\Conditions
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Helpers;

/**
 * Class IP
 *
 * A pattern is written one of four ways, and all four are read here:
 *
 *  - one address, `203.0.113.9` or `2001:db8::1`;
 *  - with a prefix, `203.0.113.0/24` or `2001:db8::/32`;
 *  - with stars, `203.0.113.*` or `203.*.113.9`, a star standing for one
 *    whole part. In IPv6 every group is written out (`2001:db8:*`, never
 *    `2001:db8::*`, which hides how many groups the gap is), and groups
 *    left off the end are stars: `2001:db8:*` is `2001:db8::/32`;
 *  - from one address to another, `203.0.113.1-203.0.113.50`.
 *
 * Everything is worked out on the packed bytes of an address, and the two
 * families never match each other. An IPv4 address carried in an IPv6 one
 * (`::ffff:203.0.113.9`) is the IPv4 address throughout.
 */
final class IP {

	/**
	 * Whether a string is an IP address.
	 *
	 * @param string $ip What might be one.
	 *
	 * @return bool
	 */
	public static function is_valid( string $ip ): bool {
		return false !== filter_var( $ip, FILTER_VALIDATE_IP );
	}

	/**
	 * Whether a string is an address or a pattern a rule can match with.
	 *
	 * A pattern of nothing but stars, a prefix longer than the address and
	 * a range from one family to the other are not.
	 *
	 * @param string $pattern What was typed.
	 *
	 * @return bool
	 */
	public static function is_valid_pattern( string $pattern ): bool {
		$pattern = trim( $pattern );

		if ( str_contains( $pattern, '*' ) ) {
			return null !== self::wildcard_parts( $pattern );
		}

		if ( str_contains( $pattern, '/' ) ) {
			return null !== self::split_cidr( $pattern )[0];
		}

		if ( str_contains( $pattern, '-' ) ) {
			return null !== self::split_span( $pattern )[0];
		}

		return null !== self::pack( $pattern );
	}

	/**
	 * Whether any of several patterns holds an address.
	 *
	 * @param array  $patterns Addresses and patterns. Anything that is not a
	 *                         string is passed over.
	 * @param string $ip       The address, in any spelling.
	 *
	 * @return bool
	 */
	public static function matches_any( array $patterns, string $ip ): bool {
		foreach ( $patterns as $pattern ) {
			if ( is_string( $pattern ) && self::matches( $pattern, $ip ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a pattern holds an address.
	 *
	 * @param string $pattern The address or pattern.
	 * @param string $ip      The address, in any spelling.
	 *
	 * @return bool False when either is malformed, and always when the two
	 *              are different families.
	 */
	public static function matches( string $pattern, string $ip ): bool {
		$packed  = self::pack( $ip );
		$pattern = trim( $pattern );

		if ( null === $packed || '' === $pattern ) {
			return false;
		}

		if ( str_contains( $pattern, '*' ) ) {
			return self::wildcard_contains( $pattern, $packed );
		}

		if ( str_contains( $pattern, '/' ) ) {
			[ $network, $prefix ] = self::split_cidr( $pattern );

			// A network of the other family is another length, and never equal.
			return null !== $network && self::mask( $packed, $prefix ) === $network;
		}

		if ( str_contains( $pattern, '-' ) ) {
			[ $low, $high ] = self::split_span( $pattern );

			return null !== $low
				&& strlen( $low ) === strlen( $packed )
				&& strcmp( $packed, $low ) >= 0
				&& strcmp( $packed, (string) $high ) <= 0;
		}

		return self::pack( $pattern ) === $packed;
	}

	/**
	 * An address as the bytes it is: four for IPv4, sixteen for IPv6.
	 *
	 * Packed, addresses sort as bytes in the order they count in, and an
	 * IPv4-mapped IPv6 address is the four bytes of the IPv4 one it carries.
	 *
	 * @param string $ip What might be an address, in any spelling.
	 *
	 * @return string|null
	 */
	public static function pack( string $ip ): ?string {
		$ip = trim( trim( $ip ), '[]' );

		if ( ! self::is_valid( $ip ) ) {
			return null;
		}

		$packed = inet_pton( $ip );

		if ( false === $packed ) {
			return null;
		}

		if ( 16 === strlen( $packed ) && str_starts_with( $packed, str_repeat( "\x00", 10 ) . "\xff\xff" ) ) {
			$packed = substr( $packed, 12 );
		}

		return $packed;
	}

	/**
	 * The parts of a pattern with stars in it: four for IPv4, eight for
	 * IPv6 (the groups left off the end filled in as stars), each a star or
	 * a number in its plain form.
	 *
	 * @param string $pattern The pattern.
	 *
	 * @return array|null Null when it is malformed, or every part is a star.
	 */
	private static function wildcard_parts( string $pattern ): ?array {
		if ( str_contains( $pattern, ':' ) ) {
			if ( 1 !== preg_match( '/^(?:[0-9a-f]{1,4}|\*)(?::(?:[0-9a-f]{1,4}|\*)){1,7}$/Di', $pattern ) ) {
				return null;
			}

			$parts = array_map(
				static fn( string $part ): string => '*' === $part ? '*' : ( ltrim( strtolower( $part ), '0' ) ?: '0' ),
				explode( ':', $pattern )
			);
			$parts = array_pad( $parts, 8, '*' );
		} else {
			if ( 1 !== preg_match( '/^(?:\d{1,3}|\*)(?:\.(?:\d{1,3}|\*)){3}$/D', $pattern ) ) {
				return null;
			}

			$parts = explode( '.', $pattern );

			foreach ( $parts as $index => $part ) {
				if ( '*' === $part ) {
					continue;
				}

				if ( (int) $part > 255 ) {
					return null;
				}

				$parts[ $index ] = (string) (int) $part;
			}
		}

		return count( $parts ) === count( array_keys( $parts, '*', true ) ) ? null : $parts;
	}

	/**
	 * Whether a pattern with stars takes in a packed address.
	 *
	 * @param string $pattern The pattern.
	 * @param string $packed  The address, from pack().
	 *
	 * @return bool
	 */
	private static function wildcard_contains( string $pattern, string $packed ): bool {
		$parts = self::wildcard_parts( $pattern );
		$ipv6  = null !== $parts && 8 === count( $parts );

		if ( null === $parts || strlen( $packed ) !== ( $ipv6 ? 16 : 4 ) ) {
			return false;
		}

		foreach ( $parts as $index => $part ) {
			if ( '*' === $part ) {
				continue;
			}

			$want = $ipv6 ? (int) hexdec( $part ) : (int) $part;
			$have = $ipv6 ? ( ord( $packed[ 2 * $index ] ) << 8 ) | ord( $packed[ 2 * $index + 1 ] ) : ord( $packed[ $index ] );

			if ( $want !== $have ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * A prefixed range as its packed network address and its prefix.
	 *
	 * @param string $pattern The range.
	 *
	 * @return array{0: string|null, 1: int} A null network when it is not an
	 *                                       address, a slash, and a prefix
	 *                                       no longer than the address.
	 */
	private static function split_cidr( string $pattern ): array {
		[ $address, $prefix ] = explode( '/', $pattern, 2 );

		$packed = self::pack( $address );

		if ( null === $packed || 1 !== preg_match( '/^\d{1,3}$/D', $prefix ) || (int) $prefix > 8 * strlen( $packed ) ) {
			return [ null, 0 ];
		}

		return [ self::mask( $packed, (int) $prefix ), (int) $prefix ];
	}

	/**
	 * A from-to range as its two packed ends, low end first.
	 *
	 * @param string $pattern The range.
	 *
	 * @return array{0: string|null, 1: string|null} Nulls when it is not two
	 *                                               addresses of one family.
	 */
	private static function split_span( string $pattern ): array {
		$ends = explode( '-', $pattern );

		if ( 2 !== count( $ends ) ) {
			return [ null, null ];
		}

		$from = self::pack( $ends[0] );
		$to   = self::pack( $ends[1] );

		if ( null === $from || null === $to || strlen( $from ) !== strlen( $to ) ) {
			return [ null, null ];
		}

		return strcmp( $from, $to ) > 0 ? [ $to, $from ] : [ $from, $to ];
	}

	/**
	 * A packed address with everything after the prefix set to nought.
	 *
	 * @param string $packed An address from pack().
	 * @param int    $prefix How many leading bits are the network.
	 *
	 * @return string
	 */
	private static function mask( string $packed, int $prefix ): string {
		$prefix = max( 0, min( strlen( $packed ) * 8, $prefix ) );
		$mask   = str_repeat( "\xff", intdiv( $prefix, 8 ) );

		if ( 0 !== $prefix % 8 ) {
			$mask .= chr( ( 0xff << ( 8 - $prefix % 8 ) ) & 0xff );
		}

		return $packed & str_pad( $mask, strlen( $packed ), "\x00" );
	}
}
