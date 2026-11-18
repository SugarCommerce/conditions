<?php
/**
 * Operators Class
 *
 * Provides operator definitions for different field types.
 *
 * @package     SugarCommerce\Conditions
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions;

/**
 * Class Operators
 *
 * Manages operators for different condition types.
 */
final class Operators {

	/** -------------------------------------------------------------------------
	 * Core Comparison Operators
	 * ------------------------------------------------------------------------ */

	/**
	 * Equality operators (single value).
	 *
	 * @return array<string, string>
	 */
	public static function equality(): array {
		return [
			'==' => __( 'Is', 'sugarcommerce' ),
			'!=' => __( 'Is not', 'sugarcommerce' ),
		];
	}

	/**
	 * Numeric comparison operators.
	 *
	 * @return array<string, string>
	 */
	public static function numeric(): array {
		return [
			'==' => __( 'Equal to', 'sugarcommerce' ),
			'!=' => __( 'Not equal to', 'sugarcommerce' ),
			'>'  => __( 'Greater than', 'sugarcommerce' ),
			'<'  => __( 'Less than', 'sugarcommerce' ),
			'>=' => __( 'Greater than or equal to', 'sugarcommerce' ),
			'<=' => __( 'Less than or equal to', 'sugarcommerce' ),
		];
	}

	/**
	 * Boolean operators.
	 *
	 * @return array<string, string>
	 */
	public static function boolean(): array {
		return [
			'yes' => __( 'Yes', 'sugarcommerce' ),
			'no'  => __( 'No', 'sugarcommerce' ),
		];
	}

	/** -------------------------------------------------------------------------
	 * Text Operators
	 * ------------------------------------------------------------------------ */

	/**
	 * Text comparison operators.
	 *
	 * @return array<string, string>
	 */
	public static function text(): array {
		return [
			'=='           => __( 'Equals', 'sugarcommerce' ),
			'!='           => __( 'Does not equal', 'sugarcommerce' ),
			'contains'     => __( 'Contains', 'sugarcommerce' ),
			'not_contains' => __( 'Does not contain', 'sugarcommerce' ),
			'starts_with'  => __( 'Starts with', 'sugarcommerce' ),
			'ends_with'    => __( 'Ends with', 'sugarcommerce' ),
		];
	}

	/**
	 * Text comparison operators with regex support.
	 *
	 * @return array<string, string>
	 */
	public static function text_advanced(): array {
		return array_merge( self::text(), [
			'regex' => __( 'Matches pattern', 'sugarcommerce' ),
		] );
	}

	/** -------------------------------------------------------------------------
	 * Collection Operators (Arrays/Multi-select)
	 * ------------------------------------------------------------------------ */

	/**
	 * Collection operators for checking membership.
	 *
	 * Used for: select (multiple), post, term, ajax types.
	 *
	 * @return array<string, string>
	 */
	public static function collection(): array {
		return [
			'any'  => __( 'Is any of', 'sugarcommerce' ),
			'none' => __( 'Is none of', 'sugarcommerce' ),
			'all'  => __( 'Is all of', 'sugarcommerce' ),
		];
	}

	/**
	 * Collection operators without "all" option.
	 *
	 * Used when "all" doesn't make logical sense (e.g., user type).
	 *
	 * @return array<string, string>
	 */
	public static function collection_any_none(): array {
		return [
			'any'  => __( 'Is any of', 'sugarcommerce' ),
			'none' => __( 'Is none of', 'sugarcommerce' ),
		];
	}

	/**
	 * Contains operators for presence checking.
	 *
	 * Used for checking if something contains/has a value.
	 * Example: "Has shortcode", "Has block", "Contains tag".
	 *
	 * @return array<string, string>
	 */
	public static function contains(): array {
		return [
			'==' => __( 'Contains', 'sugarcommerce' ),
			'!=' => __( 'Does not contain', 'sugarcommerce' ),
		];
	}

	/** -------------------------------------------------------------------------
	 * Date/Time Operators
	 * ------------------------------------------------------------------------ */

	/**
	 * Date comparison operators.
	 *
	 * @return array<string, string>
	 */
	public static function date(): array {
		return [
			'==' => __( 'Is', 'sugarcommerce' ),
			'!=' => __( 'Is not', 'sugarcommerce' ),
			'>'  => __( 'Is after', 'sugarcommerce' ),
			'<'  => __( 'Is before', 'sugarcommerce' ),
			'>=' => __( 'Is on or after', 'sugarcommerce' ),
			'<=' => __( 'Is on or before', 'sugarcommerce' ),
		];
	}

	/**
	 * Time comparison operators.
	 *
	 * @return array<string, string>
	 */
	public static function time(): array {
		return [
			'==' => __( 'Is', 'sugarcommerce' ),
			'!=' => __( 'Is not', 'sugarcommerce' ),
			'>'  => __( 'Is after', 'sugarcommerce' ),
			'<'  => __( 'Is before', 'sugarcommerce' ),
		];
	}

	/** -------------------------------------------------------------------------
	 * Pattern Matching Operators
	 * ------------------------------------------------------------------------ */

	/**
	 * IP address operators.
	 *
	 * Supports exact match, CIDR notation, and wildcards.
	 *
	 * @return array<string, string>
	 */
	public static function ip(): array {
		return [
			'ip_match'     => __( 'Matches', 'sugarcommerce' ),
			'ip_not_match' => __( 'Does not match', 'sugarcommerce' ),
		];
	}

	/**
	 * Email address operators.
	 *
	 * Supports full email, @domain.com, .tld, and partial domain matching.
	 *
	 * @return array<string, string>
	 */
	public static function email(): array {
		return [
			'email_match'     => __( 'Matches', 'sugarcommerce' ),
			'email_not_match' => __( 'Does not match', 'sugarcommerce' ),
		];
	}

	/**
	 * Tags/pattern operators - all matching modes.
	 *
	 * @return array<string, string>
	 */
	public static function tags(): array {
		return [
			'any_exact'     => __( 'Is any of', 'sugarcommerce' ),
			'none_exact'    => __( 'Is none of', 'sugarcommerce' ),
			'any_contains'  => __( 'Contains any of', 'sugarcommerce' ),
			'none_contains' => __( 'Contains none of', 'sugarcommerce' ),
			'any_starts'    => __( 'Starts with any of', 'sugarcommerce' ),
			'none_starts'   => __( 'Starts with none of', 'sugarcommerce' ),
			'any_ends'      => __( 'Ends with any of', 'sugarcommerce' ),
			'none_ends'     => __( 'Ends with none of', 'sugarcommerce' ),
		];
	}

	/**
	 * Tags operators - suffix matching only (ends with).
	 *
	 * Common use case for domain matching.
	 *
	 * @return array<string, string>
	 */
	public static function tags_ends(): array {
		return [
			'any_ends'  => __( 'Ends with any of', 'sugarcommerce' ),
			'none_ends' => __( 'Ends with none of', 'sugarcommerce' ),
		];
	}

	/**
	 * Tags operators - exact matching only.
	 *
	 * Used for identifiers (ASN, codes, IDs) where partial matching doesn't apply.
	 *
	 * Convince helper method, not passed in the JS
	 *
	 * @return array<string, string>
	 */
	public static function tags_exact(): array {
		return [
			'any_exact'  => __( 'Is any of', 'sugarcommerce' ),
			'none_exact' => __( 'Is none of', 'sugarcommerce' ),
		];
	}

	/** -------------------------------------------------------------------------
	 * Type Resolution
	 * ------------------------------------------------------------------------ */

	/**
	 * Get operators for a specific field type.
	 *
	 * @param string $type     The field type.
	 * @param bool   $multiple Whether multiple selection is enabled.
	 *
	 * @return array<string, string>
	 */
	public static function for_type( string $type, bool $multiple = false ): array {
		return match ( $type ) {
			'number', 'number_unit' => self::numeric(),
			'text_unit' => self::text(),
			'boolean' => self::boolean(),
			'date' => self::date(),
			'time' => self::time(),
			'ip' => self::ip(),
			'email' => self::email(),
			'tags' => self::tags(),
			'select', 'post', 'term', 'ajax' => $multiple ? self::collection() : self::equality(),
			'user' => $multiple ? self::collection_any_none() : self::equality(),
			default => self::text(),
		};
	}

	/**
	 * Get all operators grouped by type.
	 *
	 * Used for passing to JavaScript.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_all(): array {
		return [
			'text'             => self::text(),
			'text_advanced'    => self::text_advanced(),
			'number'           => self::numeric(),
			'boolean'          => self::boolean(),
			'date'             => self::date(),
			'time'             => self::time(),
			'ip'               => self::ip(),
			'email'            => self::email(),
			'tags'             => self::tags(),
			'tags_ends'        => self::tags_ends(),
			'equality'         => self::equality(),
			'contains'         => self::contains(),
			'collection'       => self::collection(),
			'collection_basic' => self::collection_any_none(),
		];
	}
}
