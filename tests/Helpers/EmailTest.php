<?php
/**
 * What an email rule matches, and the signals an address gives off.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Tests\Helpers;

use SugarCommerce\Conditions\Admin\TypeSanitizer;
use SugarCommerce\Conditions\Comparator;
use SugarCommerce\Conditions\Conditions;
use SugarCommerce\Conditions\Helpers\Email;
use PHPUnit\Framework\TestCase;

/**
 * Class EmailTest
 */
final class EmailTest extends TestCase {

	protected function setUp(): void {
		Conditions::reset();
		wpc_reset();
	}

	/**
	 * An address, a domain, an ending and a domain with its subdomains.
	 */
	public function test_each_kind_of_pattern_matches_what_it_describes(): void {
		$this->assertTrue( Email::matches_any( 'Jo@Example.com', [ 'jo@example.com' ] ) );
		$this->assertFalse( Email::matches_any( 'jo@example.com', [ 'al@example.com' ] ) );
		$this->assertTrue( Email::matches_any( 'jo@example.com', [ '@example.com' ] ) );
		$this->assertFalse( Email::matches_any( 'jo@mail.example.com', [ '@example.com' ] ), '@domain is that domain only.' );
		$this->assertTrue( Email::matches_any( 'jo@ox.ac.uk', [ '.ac.uk' ] ) );
		$this->assertTrue( Email::matches_any( 'jo@mail.example.com', [ 'example.com' ] ) );
		$this->assertFalse( Email::matches_any( 'jo@notexample.com', [ 'example.com' ] ), 'A domain matched the end of another word.' );
		$this->assertFalse( Email::matches_any( 'not an address', [ 'example.com' ] ) );
	}

	/**
	 * The comparator's email operators, including no address at all.
	 */
	public function test_the_email_operators(): void {
		$comparator = new Comparator( 'email' );

		$this->assertTrue( $comparator->compare( 'email_match', [ '@example.com' ], 'jo@example.com' ) );
		$this->assertFalse( $comparator->compare( 'email_not_match', [ '@example.com' ], 'jo@example.com' ) );
		$this->assertFalse( $comparator->compare( 'email_match', [ '@example.com' ], '' ) );
		$this->assertTrue( $comparator->compare( 'email_not_match', [ '@example.com' ], 'garbage' ) );
		$this->assertTrue( $comparator->compare( 'email_not_match', [], 'jo@example.com' ), 'No patterns, nothing to match.' );
	}

	/**
	 * What a saved email rule keeps.
	 */
	public function test_patterns_are_cleaned_on_save(): void {
		$this->assertSame(
			[ 'jo@example.com', '@example.com', '.edu', 'example.co.uk' ],
			TypeSanitizer::email( [ 'Jo@Example.com', '@Example.com', '.edu', 'example.co.uk', '@nodot', '.e', 'not a pattern', [ 'x' ] ] )
		);
	}

	/**
	 * Free providers, and the misspellings of them people really type.
	 */
	public function test_free_providers_and_their_typos(): void {
		$this->assertTrue( Email::is_freemail( [ 'email' => 'jo@gmail.com' ] ) );
		$this->assertTrue( Email::is_freemail( [ 'email' => 'jo@gmial.com' ] ) );
		$this->assertFalse( Email::is_freemail( [ 'email' => 'jo@acme.co' ] ) );
		$this->assertFalse( Email::is_freemail( [] ) );

		add_filter( Conditions::hook( 'freemail_domains' ), static fn( array $domains ): array => [ ...$domains, 'ACME.co' ] );
		$this->assertTrue( Email::is_freemail( [ 'email' => 'jo@acme.co' ] ), 'The filter could not add a provider.' );
	}

	/**
	 * The parts and signals of an address.
	 */
	public function test_the_signals(): void {
		$args = [ 'email' => 'John.Smith+promo@Mail.Example.co.uk' ];

		$this->assertSame( 'mail.example.co.uk', Email::get_domain( $args ) );
		$this->assertSame( 'uk', Email::get_tld( $args ) );
		$this->assertTrue( Email::has_plus_alias( $args ) );
		$this->assertSame( 16, Email::get_local_length( $args ) );
		$this->assertFalse( Email::has_plus_alias( [ 'email' => 'jo@example.com' ] ) );

		$this->assertSame( 0.5, Email::get_local_digit_density( [ 'email' => 'ab12@example.com' ] ) );
		$this->assertSame( 0.0, Email::get_local_digit_density( [ 'email' => 'nope' ] ) );
	}

	/**
	 * The suspicion score adds its signals up and stops at 100.
	 */
	public function test_the_suspicion_score(): void {
		$this->assertSame( 0, Email::get_local_suspicion_score( [ 'email' => 'jo@example.com' ] ) );
		$this->assertSame( 0, Email::get_local_suspicion_score( [ 'email' => 'john.smith@example.com' ] ), 'One dot is a name, not a signal.' );
		$this->assertSame( 0, Email::get_local_suspicion_score( [ 'email' => 'not an address' ] ) );

		// 4 of 8 digits: 17.5, rounded to 18.
		$this->assertSame( 18, Email::get_local_suspicion_score( [ 'email' => 'abcd1234@example.com' ] ) );

		// Plus alias, 20; three specials, 10; two dots, 10.
		$this->assertSame( 40, Email::get_local_suspicion_score( [ 'email' => 'a.b.c+d@example.com' ] ) );

		// 32 characters, all digits: 35 + 25.
		$this->assertSame( 60, Email::get_local_suspicion_score( [ 'email' => str_repeat( '1', 32 ) . '@example.com' ] ) );

		// Every signal at once: 31 of 34 digits, long, aliased, dotted, special.
		$this->assertSame( 97, Email::get_local_suspicion_score( [ 'email' => str_repeat( '1', 28 ) . '.1.1+1@example.com' ] ) );
	}
}
