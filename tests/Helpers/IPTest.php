<?php
/**
 * What an IP rule matches: sugarcommerce/network's Range, through the
 * comparator and the sanitiser that hold a rule's patterns.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Tests\Helpers;

use SugarCommerce\Conditions\Comparator;
use SugarCommerce\Conditions\Admin\TypeSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Class IPTest
 */
final class IPTest extends TestCase {

	/**
	 * Whether an `ip_match` rule holding these patterns matches an address.
	 *
	 * @param string $ip       The address.
	 * @param array  $patterns The rule's patterns.
	 *
	 * @return bool
	 */
	private static function rule_matches( string $ip, array $patterns ): bool {
		return ( new Comparator( 'ip' ) )->compare( 'ip_match', $patterns, $ip );
	}

	/**
	 * Whether the sanitiser keeps a pattern.
	 *
	 * @param string $pattern The pattern.
	 *
	 * @return bool
	 */
	private static function kept( string $pattern ): bool {
		return '' !== TypeSanitizer::ip( $pattern );
	}

	/**
	 * Exact addresses, ranges and wildcards, in both families.
	 */
	public function test_each_kind_of_pattern_matches_what_it_describes(): void {
		$this->assertTrue( self::rule_matches( '203.0.113.9', [ '203.0.113.9' ] ) );
		$this->assertTrue( self::rule_matches( '203.0.113.9', [ '203.0.113.0/24' ] ) );
		$this->assertFalse( self::rule_matches( '203.0.114.9', [ '203.0.113.0/24' ] ) );
		$this->assertTrue( self::rule_matches( '203.0.113.9', [ '203.0.113.*' ] ) );
		$this->assertFalse( self::rule_matches( '203.0.113.9', [ '203.0.112.*' ] ) );
		$this->assertTrue( self::rule_matches( '10.1.2.3', [ '10.0.0.0/8' ] ) );
		$this->assertTrue( self::rule_matches( '10.1.2.3', [ '0.0.0.0/0' ] ) );
		$this->assertTrue( self::rule_matches( '203.0.113.128', [ '203.0.113.128/25' ] ) );
		$this->assertFalse( self::rule_matches( '203.0.113.127', [ '203.0.113.128/25' ] ) );

		$this->assertTrue( self::rule_matches( '2001:db8::1', [ '2001:db8::/32' ] ) );
		$this->assertFalse( self::rule_matches( '2001:db9::1', [ '2001:db8::/32' ] ) );
		$this->assertTrue( self::rule_matches( '2001:db8:0:5::1', [ '2001:db8:0:4::/63' ] ) );
		$this->assertFalse( self::rule_matches( '2001:db8:0:6::1', [ '2001:db8:0:4::/63' ] ) );
	}

	/**
	 * An IPv6 address is the same address however it is written.
	 */
	public function test_ipv6_spellings_are_one_address(): void {
		$this->assertTrue( self::rule_matches( '2001:DB8:0::1', [ '2001:db8::1' ] ) );
		$this->assertTrue( self::rule_matches( '2001:db8::1', [ '2001:0db8:0000:0000:0000:0000:0000:0001' ] ) );
	}

	/**
	 * An IPv6 wildcard names groups the compressed spelling hides.
	 */
	public function test_an_ipv6_wildcard_reads_the_address_in_full(): void {
		$this->assertTrue( self::rule_matches( '2001:db8::1', [ '2001:db8:*' ] ) );
		$this->assertTrue( self::rule_matches( '2001:db8:85a3::1', [ '2001:0db8:*' ] ) );
		$this->assertFalse( self::rule_matches( '2001:db9::1', [ '2001:db8:*' ] ) );
		$this->assertFalse( self::rule_matches( '203.0.113.9', [ '2001:db8:*' ] ) );
	}

	/**
	 * What Range adds: a from-to range, and an IPv4 address an IPv6 socket
	 * reported, which is the IPv4 address it carries.
	 */
	public function test_ranges_and_mapped_addresses(): void {
		$this->assertTrue( self::rule_matches( '203.0.113.20', [ '203.0.113.10-203.0.113.50' ] ) );
		$this->assertFalse( self::rule_matches( '203.0.113.51', [ '203.0.113.10-203.0.113.50' ] ) );
		$this->assertTrue( self::kept( '203.0.113.10-203.0.113.50' ), 'The sanitiser dropped a pattern the comparator matches.' );
		$this->assertTrue( self::rule_matches( '::ffff:203.0.113.9', [ '203.0.113.0/24' ] ) );
		$this->assertTrue( self::rule_matches( '203.0.113.9', [ ' 203.0.113.9 ' ] ) );
	}

	/**
	 * A range of the other family is a miss, not an error.
	 */
	public function test_mixed_families_miss_quietly(): void {
		$this->assertFalse( self::rule_matches( '203.0.113.9', [ '2001:db8::/32' ] ) );
		$this->assertFalse( self::rule_matches( '2001:db8::1', [ '203.0.113.0/24' ] ) );
		$this->assertFalse( self::rule_matches( '203.0.113.9', [ '203.0.113.0/33' ] ) );

		// 32.1.13.184 is the bytes 20 01 0d b8: the first four of 2001:db8::.
		$this->assertFalse( self::rule_matches( '32.1.13.184', [ '2001:db8::/32' ] ), 'An IPv4 address matched an IPv6 range byte for byte.' );
		$this->assertFalse( self::rule_matches( 'not an ip', [ '*.*.*.*', '0.0.0.0/0' ] ) );
	}

	/**
	 * A pattern of nothing but stars is not a pattern anybody meant.
	 */
	public function test_an_all_star_pattern_is_not_valid(): void {
		$this->assertTrue( self::kept( '203.0.113.*' ) );
		$this->assertTrue( self::kept( '2001:db8::/32' ) );
		$this->assertFalse( self::kept( '*.*.*.*' ) );
		$this->assertFalse( self::kept( '*' ) );
		$this->assertFalse( self::kept( 'evil.*' ) );
		$this->assertFalse( self::rule_matches( '203.0.113.9', [ '*.*.*.*' ] ) );
	}

	/**
	 * The comparator's IP operators, including no address at all.
	 */
	public function test_the_ip_operators(): void {
		$comparator = new Comparator( 'ip' );

		$this->assertTrue( $comparator->compare( 'ip_match', [ '203.0.113.0/24' ], '203.0.113.9' ) );
		$this->assertFalse( $comparator->compare( 'ip_not_match', [ '203.0.113.0/24' ], '203.0.113.9' ) );
		$this->assertTrue( $comparator->compare( 'ip_not_match', [ '203.0.113.0/24' ], '198.51.100.1' ) );
		$this->assertFalse( $comparator->compare( 'ip_match', [ '203.0.113.0/24' ], '' ), 'No address matched a list.' );
		$this->assertTrue( $comparator->compare( 'ip_not_match', [ '203.0.113.0/24' ], null ) );
	}
}
