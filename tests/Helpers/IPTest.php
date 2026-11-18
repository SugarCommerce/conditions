<?php
/**
 * What an IP rule matches.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Tests\Helpers;

use SugarCommerce\Conditions\Comparator;
use SugarCommerce\Conditions\Helpers\IP;
use PHPUnit\Framework\TestCase;

/**
 * Class IPTest
 */
final class IPTest extends TestCase {

	/**
	 * Exact addresses, ranges and wildcards, in both families.
	 */
	public function test_each_kind_of_pattern_matches_what_it_describes(): void {
		$this->assertTrue( IP::is_match( '203.0.113.9', [ '203.0.113.9' ] ) );
		$this->assertTrue( IP::is_match( '203.0.113.9', [ '203.0.113.0/24' ] ) );
		$this->assertFalse( IP::is_match( '203.0.114.9', [ '203.0.113.0/24' ] ) );
		$this->assertTrue( IP::is_match( '203.0.113.9', [ '203.0.113.*' ] ) );
		$this->assertFalse( IP::is_match( '203.0.113.9', [ '203.0.112.*' ] ) );
		$this->assertTrue( IP::is_match( '10.1.2.3', [ '10.0.0.0/8' ] ) );
		$this->assertTrue( IP::is_match( '10.1.2.3', [ '0.0.0.0/0' ] ) );
		$this->assertTrue( IP::is_match( '203.0.113.128', [ '203.0.113.128/25' ] ) );
		$this->assertFalse( IP::is_match( '203.0.113.127', [ '203.0.113.128/25' ] ) );

		$this->assertTrue( IP::is_match( '2001:db8::1', [ '2001:db8::/32' ] ) );
		$this->assertFalse( IP::is_match( '2001:db9::1', [ '2001:db8::/32' ] ) );
		$this->assertTrue( IP::is_match( '2001:db8:0:5::1', [ '2001:db8:0:4::/63' ] ) );
		$this->assertFalse( IP::is_match( '2001:db8:0:6::1', [ '2001:db8:0:4::/63' ] ) );
	}

	/**
	 * An IPv6 address is the same address however it is written.
	 */
	public function test_ipv6_spellings_are_one_address(): void {
		$this->assertTrue( IP::is_match( '2001:DB8:0::1', [ '2001:db8::1' ] ) );
		$this->assertTrue( IP::is_match( '2001:db8::1', [ '2001:0db8:0000:0000:0000:0000:0000:0001' ] ) );
	}

	/**
	 * An IPv6 wildcard names groups the compressed spelling hides.
	 */
	public function test_an_ipv6_wildcard_reads_the_address_in_full(): void {
		$this->assertTrue( IP::is_match( '2001:db8::1', [ '2001:db8:*' ] ) );
		$this->assertTrue( IP::is_match( '2001:db8:85a3::1', [ '2001:0db8:*' ] ) );
		$this->assertFalse( IP::is_match( '2001:db9::1', [ '2001:db8:*' ] ) );
		$this->assertFalse( IP::is_match( '203.0.113.9', [ '2001:db8:*' ] ) );
	}

	/**
	 * A range of the other family is a miss, not an error.
	 */
	public function test_mixed_families_miss_quietly(): void {
		$this->assertFalse( IP::is_match( '203.0.113.9', [ '2001:db8::/32' ] ) );
		$this->assertFalse( IP::is_match( '2001:db8::1', [ '203.0.113.0/24' ] ) );
		$this->assertFalse( IP::is_in_range( '203.0.113.9', '203.0.113.0/33' ) );

		// 32.1.13.184 is the bytes 20 01 0d b8: the first four of 2001:db8::.
		$this->assertFalse( IP::is_in_range( '32.1.13.184', '2001:db8::/32' ), 'An IPv4 address matched an IPv6 range byte for byte.' );
		$this->assertFalse( IP::is_match( 'not an ip', [ '*.*.*.*', '0.0.0.0/0' ] ) );
	}

	/**
	 * A pattern of nothing but stars is not a pattern anybody meant.
	 */
	public function test_an_all_star_pattern_is_not_valid(): void {
		$this->assertTrue( IP::is_valid_pattern( '203.0.113.*' ) );
		$this->assertTrue( IP::is_valid_pattern( '2001:db8::/32' ) );
		$this->assertFalse( IP::is_valid_pattern( '*.*.*.*' ) );
		$this->assertFalse( IP::is_valid_pattern( '*' ) );
		$this->assertFalse( IP::is_valid_pattern( 'evil.*' ) );
		$this->assertFalse( IP::is_match( '203.0.113.9', [ '*.*.*.*' ] ) );
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
