<?php
/**
 * Email Helper
 *
 * Reads an email address into the parts the email rules ask about, and
 * matches it against the patterns an admin types into an email rule.
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
 * Class Email
 *
 * Utilities for email-pattern conditions.
 */
final class Email {

	/**
	 * The free webmail providers, before the filter.
	 *
	 * @var string[]
	 */
	private const array FREEMAIL = [
		'gmail.com',
		'googlemail.com',
		'yahoo.com',
		'hotmail.com',
		'outlook.com',
		'aol.com',
		'icloud.com',
		'me.com',
		'protonmail.com',
		'proton.me',
		'mail.com',
		'zoho.com',
		'live.com',
		'msn.com',
		'gmx.com',
		'yandex.com',
	];

	/**
	 * Misspellings of those providers that people really type.
	 *
	 * A typo of a free provider is still somebody with a free address --
	 * one they will not receive mail at.
	 *
	 * @var string[]
	 */
	private const array FREEMAIL_TYPOS = [
		'gmial.com',
		'gmali.com',
		'gmai.com',
		'gmail.co',
		'gmal.com',
		'gnail.com',
		'gmaill.com',
		'gamil.com',
		'gmailc.om',
		'gmail.cm',
		'gmail.om',
		'gmail.cim',
		'gmail.con',
		'gmaik.com',
		'gmsil.com',
		'gmeil.com',
		'fmail.com',
		'hmail.com',
		'yaho.com',
		'yahooo.com',
		'yhoo.com',
		'yahho.com',
		'yhaoo.com',
		'yaoo.com',
		'yahoo.co',
		'yahoo.cm',
		'yahoo.con',
		'tahoo.com',
		'uahoo.com',
		'hotmal.com',
		'hotmai.com',
		'hotmial.com',
		'hotamil.com',
		'hotmali.com',
		'hotmaill.com',
		'hotmil.com',
		'hotmail.co',
		'hotmail.cm',
		'hotmail.con',
		'hitmail.com',
		'hotnail.com',
		'homail.com',
		'outlok.com',
		'outloo.com',
		'outlool.com',
		'outloook.com',
		'outluk.com',
		'outllok.com',
		'outlook.co',
		'outlook.cm',
		'outlook.con',
		'putlook.com',
		'outoolk.com',
		'iclod.com',
		'icoud.com',
		'icloud.co',
		'icloud.cm',
		'icloud.con',
		'icluod.com',
		'iclould.com',
		'aol.co',
		'aol.cm',
		'aol.con',
		'aoll.com',
		'ao.com',
		'protonmal.com',
		'protonmai.com',
		'protonmail.co',
		'protonmail.cm',
		'protonmail.con',
		'protonmial.com',
		'protnmail.com',
		'live.co',
		'live.cm',
		'live.con',
		'liv.com',
		'livee.com',
	];

	/** Parsing *******************************************************************/

	/**
	 * Read an address into its parts.
	 *
	 * @param string $email The address.
	 *
	 * @return array{email: string, local: string, domain: string, tld: string}|null
	 *         Lower-cased parts, or null when it is not an address.
	 */
	public static function parse( string $email ): ?array {
		$email = strtolower( trim( $email ) );

		if ( '' === $email || ! self::is_valid( $email ) ) {
			return null;
		}

		$at     = (int) strrpos( $email, '@' );
		$domain = substr( $email, $at + 1 );
		$dot    = strrpos( $domain, '.' );

		return [
			'email'  => $email,
			'local'  => substr( $email, 0, $at ),
			'domain' => $domain,
			'tld'    => false === $dot ? '' : substr( $domain, $dot + 1 ),
		];
	}

	/**
	 * Whether a string is an email address, by WordPress's reading of one.
	 *
	 * @param string $email The value.
	 *
	 * @return bool
	 */
	public static function is_valid( string $email ): bool {
		return function_exists( 'is_email' )
			? false !== is_email( $email )
			: false !== filter_var( $email, FILTER_VALIDATE_EMAIL );
	}

	/** Patterns ******************************************************************/

	/**
	 * Whether an address matches any of a rule's patterns.
	 *
	 *  - `jo@example.com` -- that address;
	 *  - `@example.com`   -- anyone at that domain;
	 *  - `.edu`           -- anyone at a domain ending so;
	 *  - `example.com`    -- that domain and its subdomains.
	 *
	 * @param string $email    The address.
	 * @param array  $patterns The patterns.
	 *
	 * @return bool
	 */
	public static function matches_any( string $email, array $patterns ): bool {
		$parsed = self::parse( $email );

		if ( null === $parsed ) {
			return false;
		}

		foreach ( $patterns as $pattern ) {
			if ( is_scalar( $pattern ) && self::matches( $parsed, strtolower( trim( (string) $pattern ) ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a parsed address matches one pattern.
	 *
	 * @param array  $parsed  From parse().
	 * @param string $pattern A lower-cased pattern.
	 *
	 * @return bool
	 */
	private static function matches( array $parsed, string $pattern ): bool {
		if ( '' === $pattern ) {
			return false;
		}

		if ( str_starts_with( $pattern, '@' ) || str_starts_with( $pattern, '.' ) ) {
			return str_ends_with( $parsed['email'], $pattern );
		}

		if ( str_contains( $pattern, '@' ) ) {
			return $pattern === $parsed['email'];
		}

		return $parsed['domain'] === $pattern || str_ends_with( $parsed['domain'], '.' . $pattern );
	}

	/**
	 * Clean one pattern for an email rule, or '' when it is not one.
	 *
	 * @param mixed $value A pattern as typed.
	 *
	 * @return string
	 */
	public static function sanitize_pattern( mixed $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = strtolower( trim( function_exists( 'sanitize_text_field' ) ? sanitize_text_field( (string) $value ) : (string) $value ) );

		if ( '' === $value ) {
			return '';
		}

		if ( null !== self::parse( $value ) ) {
			return $value;
		}

		if ( str_starts_with( $value, '@' ) ) {
			return self::is_domain( substr( $value, 1 ) ) ? $value : '';
		}

		if ( str_starts_with( $value, '.' ) ) {
			return 1 === preg_match( '/^(\.[a-z]{2,})+$/', $value ) ? $value : '';
		}

		return self::is_domain( $value ) ? $value : '';
	}

	/**
	 * Whether a string is shaped like a domain: dotted, letters, digits, hyphens.
	 *
	 * @param string $domain The value.
	 *
	 * @return bool
	 */
	private static function is_domain( string $domain ): bool {
		if ( ! str_contains( $domain, '.' ) ) {
			return false;
		}

		foreach ( explode( '.', $domain ) as $label ) {
			if ( '' === $label || ! ctype_alnum( str_replace( '-', '', $label ) ) ) {
				return false;
			}
		}

		return true;
	}

	/** Signals *******************************************************************/

	/**
	 * The `email` argument, parsed.
	 *
	 * @param array $args Condition args.
	 *
	 * @return array|null
	 */
	private static function from( array $args ): ?array {
		return is_scalar( $args['email'] ?? null ) ? self::parse( (string) $args['email'] ) : null;
	}

	/**
	 * The free webmail domains, filterable: `{prefix}_conditions_freemail_domains`.
	 *
	 * @return string[]
	 */
	public static function freemail_domains(): array {
		$domains = self::FREEMAIL;

		if ( function_exists( 'apply_filters' ) ) {
			$domains = (array) apply_filters( Conditions::hook( 'freemail_domains' ), $domains );
		}

		return array_values( array_map( 'strtolower', array_filter( $domains, 'is_string' ) ) );
	}

	/**
	 * Whether the email is from a free / common provider (gmail, yahoo, etc),
	 * or a misspelling of one.
	 *
	 * @param array $args Condition args.
	 *
	 * @return bool
	 */
	public static function is_freemail( array $args ): bool {
		$email = self::from( $args );

		return null !== $email
			&& ( in_array( $email['domain'], self::freemail_domains(), true ) || in_array( $email['domain'], self::FREEMAIL_TYPOS, true ) );
	}

	/**
	 * Get the email's domain (e.g. `gmail.com`).
	 *
	 * @param array $args Condition args.
	 *
	 * @return string
	 */
	public static function get_domain( array $args ): string {
		return self::from( $args )['domain'] ?? '';
	}

	/**
	 * Get the email's TLD: the part after the last dot (`com`, `uk`).
	 *
	 * @param array $args Condition args.
	 *
	 * @return string
	 */
	public static function get_tld( array $args ): string {
		return self::from( $args )['tld'] ?? '';
	}

	/**
	 * Get the length of the local-part (before the `@`).
	 *
	 * Auto-generated fraud emails skew long.
	 *
	 * @param array $args Condition args.
	 *
	 * @return int
	 */
	public static function get_local_length( array $args ): int {
		return strlen( self::from( $args )['local'] ?? '' );
	}

	/**
	 * Whether the email uses plus-aliasing (`foo+tag@gmail.com`).
	 *
	 * @param array $args Condition args.
	 *
	 * @return bool
	 */
	public static function has_plus_alias( array $args ): bool {
		return str_contains( self::from( $args )['local'] ?? '', '+' );
	}

	/**
	 * Get the digit-density of the local-part (0.0 - 1.0).
	 *
	 * High density (e.g. >0.5) often indicates an auto-generated fraud email.
	 *
	 * @param array $args Condition args.
	 *
	 * @return float
	 */
	public static function get_local_digit_density( array $args ): float {
		$local = self::from( $args )['local'] ?? '';
		$len   = strlen( $local );

		if ( 0 === $len ) {
			return 0.0;
		}

		return round( strlen( (string) preg_replace( '/\D/', '', $local ) ) / $len, 3 );
	}

	/**
	 * Compute a 0-100 suspicion score for the local-part of the email.
	 *
	 * Combines several individual signals into a single tunable threshold,
	 * so a merchant can write `score >= 60 → review` rather than building
	 * a multi-condition AND group out of length / digit-density / aliases /
	 * specials. Each signal contributes weighted points up to a 100 cap.
	 *
	 *   Digit density            up to 35 pts (linear: density × 35)
	 *   Length over 16 chars     up to 25 pts (linear over 16-32 char range)
	 *   Has plus alias           20 pts flat
	 *   Multiple dots in local   10 pts (1 dot = 0, 2+ = 10)
	 *   Excessive specials       10 pts (consecutive _- or 3+ specials total)
	 *
	 * Sensible thresholds: under 30 almost certainly legitimate, 30-59
	 * borderline, 60-79 worth a review, 80 and up auto-generated.
	 *
	 * @param array $args Condition args.
	 *
	 * @return int 0-100 score; 0 for no address.
	 */
	public static function get_local_suspicion_score( array $args ): int {
		$local = self::from( $args )['local'] ?? '';
		$len   = strlen( $local );

		if ( 0 === $len ) {
			return 0;
		}

		$digits = strlen( (string) preg_replace( '/\D/', '', $local ) );
		$score  = min( 35.0, ( $digits / $len ) * 35.0 );

		if ( $len > 16 ) {
			$score += ( min( 16, $len - 16 ) / 16 ) * 25.0;
		}

		if ( str_contains( $local, '+' ) ) {
			$score += 20.0;
		}

		if ( substr_count( $local, '.' ) >= 2 ) {
			$score += 10.0;
		}

		if ( preg_match_all( '/[._\-+]/', $local ) >= 3 || preg_match( '/[._\-+]{2,}/', $local ) ) {
			$score += 10.0;
		}

		return (int) min( 100, round( $score ) );
	}
}
