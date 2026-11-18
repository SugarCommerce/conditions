<?php
/**
 * Type Sanitizer
 *
 * Handles sanitization of values based on field type and configuration.
 *
 * @package     SugarCommerce\Conditions\Admin
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Admin;

use DateTime;
use SugarCommerce\Conditions\Helpers\Email;
use SugarCommerce\Conditions\Helpers\IP;

/**
 * Class TypeSanitizer
 *
 * Provides type-aware sanitization for condition values.
 */
final class TypeSanitizer {

	/**
	 * Sanitize a value based on its field type and configuration.
	 *
	 * If a custom sanitize callback is provided in the config, it takes priority.
	 * Otherwise, type-specific sanitization is applied using field metadata
	 * like min, max, step, and allowed values.
	 *
	 * @param mixed $value  The value to sanitize.
	 * @param array $config The condition configuration.
	 *
	 * @return mixed The sanitized value.
	 */
	public static function sanitize( mixed $value, array $config = [] ): mixed {
		// Custom callback takes priority
		if ( ! empty( $config['sanitize'] ) && is_callable( $config['sanitize'] ) ) {
			return call_user_func( $config['sanitize'], $value, $config );
		}

		if ( is_null( $value ) ) {
			return null;
		}

		$type = $config['type'] ?? 'text';

		return match ( $type ) {
			'number' => self::number( $value, $config ),
			'number_unit' => self::number_unit( $value, $config ),
			'text_unit' => self::text_unit( $value, $config ),
			'date' => self::date( $value ),
			'time' => self::time( $value ),
			'email' => self::email( $value ),
			'ip' => self::ip( $value ),
			'tags' => self::tags( $value ),
			'boolean' => null,
			'select' => self::selection( $value, $config ),
			'post', 'term', 'user', 'ajax' => self::selection( $value, $config ),
			default => self::text( $value ),
		};
	}

	/**
	 * Sanitize a text value.
	 *
	 * @param mixed $value The value to sanitize.
	 *
	 * @return string The sanitized text.
	 */
	public static function text( mixed $value ): string {
		// An array cast to string is the word "Array" and a warning, which is
		// what a condition switched from multiple to single would have saved.
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitize a number value with optional constraints.
	 *
	 * Determines whether to return an integer or float based on the step value.
	 * Applies min/max constraints if defined in the configuration.
	 *
	 * @param mixed $value  The value to sanitize.
	 * @param array $config The condition configuration with optional min, max, step.
	 *
	 * @return float|int|string The sanitized number, or empty string if no value.
	 */
	public static function number( mixed $value, array $config = [] ): float|int|string {
		// Allow empty values (rule will be skipped by is_empty check)
		if ( $value === '' || $value === null ) {
			return '';
		}

		// A value that is not a number is no value. It used to become the
		// minimum, or nought, so "order total > abc" was saved and evaluated
		// as "order total > 0" -- a rule the admin never wrote.
		if ( ! is_numeric( $value ) ) {
			return '';
		}

		// Determine if integer based on step
		$step       = $config['step'] ?? 1;
		// Loose on purpose: this asks whether the step is a whole number, and
		// a step of 1.0 has to answer yes. Strict comparison would say no for
		// every float step and quietly turn integer fields into float ones.
		// phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
		$is_integer = ( $step == (int) $step && $step >= 1 );

		$value = $is_integer ? (int) $value : (float) $value;

		// Apply min constraint
		if ( isset( $config['min'] ) ) {
			$value = max( $value, $config['min'] );
		}

		// Apply max constraint
		if ( isset( $config['max'] ) ) {
			$value = min( $value, $config['max'] );
		}

		return $value;
	}

	/**
	 * Sanitize a number with unit value.
	 *
	 * Validates both the numeric portion and ensures the unit is from
	 * the allowed units list defined in the configuration.
	 *
	 * @param mixed $value  The value to sanitize (expected array with 'number' and 'unit').
	 * @param array $config The condition configuration with optional units array.
	 *
	 * @return array{number: float|int|string, unit: string} The sanitized number_unit value.
	 */
	public static function number_unit( mixed $value, array $config = [] ): array {
		if ( ! is_array( $value ) ) {
			return [
				'number' => '',
				'unit' => '',
			];
		}

		// Sanitize the number part using the same logic
		$number = self::number( $value['number'] ?? '', $config );

		// The allow-list is the validation; the unit only has to be a clean
		// string to compare. sanitize_key() lowercased it first, so a unit
		// written with a capital never matched and fell back to the first.
		$unit          = self::text( $value['unit'] ?? '' );
		$allowed_units = self::unit_values( $config );

		if ( ! empty( $allowed_units ) && ! in_array( $unit, $allowed_units, true ) ) {
			$unit = $allowed_units[0] ?? '';
		}

		return [
			'number' => $number,
			'unit'   => $unit,
		];
	}

	/**
	 * Sanitize a text with unit value.
	 *
	 * Validates the text portion and ensures the unit is from
	 * the allowed units list defined in the configuration.
	 *
	 * @param mixed $value  The value to sanitize (expected array with 'text' and 'unit').
	 * @param array $config The condition configuration with optional units array.
	 *
	 * @return array{text: string, unit: string} The sanitized text_unit value.
	 */
	public static function text_unit( mixed $value, array $config = [] ): array {
		if ( ! is_array( $value ) ) {
			return [
				'text' => '',
				'unit' => '',
			];
		}

		// Sanitize the text part
		$text = self::text( $value['text'] ?? '' );

		// See number_unit(): the allow-list validates, the cast just cleans.
		$unit          = self::text( $value['unit'] ?? '' );
		$allowed_units = self::unit_values( $config );

		if ( ! empty( $allowed_units ) && ! in_array( $unit, $allowed_units, true ) ) {
			$unit = $allowed_units[0] ?? '';
		}

		return [
			'text' => $text,
			'unit' => $unit,
		];
	}

	/**
	 * Sanitize a date value.
	 *
	 * Validates the date is in Y-m-d format and represents a valid calendar date
	 * using PHP's DateTime class for reliable parsing.
	 *
	 * @param mixed $value The value to sanitize.
	 *
	 * @return string The sanitized date in Y-m-d format, or empty string if invalid.
	 */
	public static function date( mixed $value ): string {
		$value = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';

		if ( empty( $value ) ) {
			return '';
		}

		$date = DateTime::createFromFormat( 'Y-m-d', $value );

		// Ensure the date parsed correctly and matches the input
		// (prevents things like 2024-02-31 becoming 2024-03-02)
		if ( $date && $date->format( 'Y-m-d' ) === $value ) {
			return $value;
		}

		return '';
	}

	/**
	 * Sanitize a time value.
	 *
	 * Validates the time is in H:i or H:i:s format with valid hour/minute/second ranges
	 * using PHP's DateTime class for reliable parsing.
	 *
	 * @param mixed $value The value to sanitize.
	 *
	 * @return string The sanitized time, or empty string if invalid.
	 */
	public static function time( mixed $value ): string {
		$value = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';

		if ( empty( $value ) ) {
			return '';
		}

		foreach ( [ 'H:i:s', 'H:i' ] as $format ) {
			$time = DateTime::createFromFormat( $format, $value );

			if ( $time && $time->format( $format ) === $value ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Sanitize an IP address or pattern (single or multiple).
	 *
	 * Supports single IP addresses, CIDR notation (192.168.1.0/24),
	 * and wildcard patterns (192.168.1.*).
	 *
	 * @param mixed $value The value to sanitize (string or array).
	 *
	 * @return array<string>|string The sanitized IP pattern(s).
	 */
	public static function ip( mixed $value ): array|string {
		// Handle arrays (multiple patterns from tags input)
		if ( is_array( $value ) ) {
			return array_values(
				array_filter(
					array_map( [ self::class, 'sanitize_ip_pattern' ], $value )
				)
			);
		}

		// Handle single value
		return self::sanitize_ip_pattern( $value );
	}

	/**
	 * Sanitize a single IP pattern.
	 *
	 * @param mixed $value The value to sanitize.
	 *
	 * @return string The sanitized IP pattern, or empty string if invalid.
	 */
	private static function sanitize_ip_pattern( mixed $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( sanitize_text_field( (string) $value ) );

		// An address, a CIDR range, a from-to range, or a wildcard with at
		// least one literal part -- what the comparator matches. The old
		// check accepted anything with a star in it, because the wildcard
		// test it leaned on never failed.
		return '' !== $value && IP::is_valid_pattern( $value ) ? $value : '';
	}

	/**
	 * Sanitize an email address or pattern (single or multiple).
	 *
	 * Supports full email addresses, domain patterns (@domain.com),
	 * TLD patterns (.edu), and partial domain matching using the Email class.
	 *
	 * @param mixed $value The value to sanitize (string or array).
	 *
	 * @return array<string> The sanitized email patterns as an array.
	 */
	public static function email( mixed $value ): array {
		// Handle arrays (multiple patterns)
		if ( is_array( $value ) ) {
			return array_values(
				array_filter(
					array_map( [ self::class, 'sanitize_email_pattern' ], $value )
				)
			);
		}

		// Handle single value
		$sanitized = self::sanitize_email_pattern( $value );

		return $sanitized !== '' ? [ $sanitized ] : [];
	}

	/**
	 * Sanitize a single email pattern.
	 *
	 * @param mixed $value The value to sanitize.
	 *
	 * @return string The sanitized pattern, or empty string if invalid.
	 */
	private static function sanitize_email_pattern( mixed $value ): string {
		return Email::sanitize_pattern( $value );
	}

	/**
	 * Sanitize a tags array.
	 *
	 * Filters and sanitizes an array of string tags, removing empty values.
	 *
	 * @param mixed $value The value to sanitize (expected array).
	 *
	 * @return array<string> The sanitized tags array.
	 */
	public static function tags( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		// Not array_filter() alone: that drops the tag '0', which is a real
		// tag for a rule about ids or codes.
		return array_values(
			array_filter(
				array_map( [ self::class, 'text' ], $value ),
				static fn( string $tag ): bool => '' !== $tag
			)
		);
	}

	/**
	 * Sanitize a selection value (single or multiple).
	 *
	 * Handles both single-select and multi-select fields based on
	 * the multiple flag in the configuration.
	 *
	 * @param mixed $value  The value to sanitize.
	 * @param array $config The condition configuration with optional 'multiple' flag.
	 *
	 * @return array<string>|string The sanitized selection value(s).
	 */
	public static function selection( mixed $value, array $config = [] ): array|string {
		$multiple = $config['multiple'] ?? false;

		if ( $multiple ) {
			if ( ! is_array( $value ) ) {
				return [];
			}

			return array_values(
				array_filter(
					array_unique( array_map( [ self::class, 'text' ], $value ) ),
					static fn( string $item ): bool => '' !== $item
				)
			);
		}

		return self::text( $value );
	}

	/**
	 * The values a condition's units allow.
	 *
	 * Units may be a callable, resolved when the editor is drawn -- and the
	 * save reads the raw configuration, so a callable reached array_column()
	 * as a Closure: a TypeError, and a white screen, for every rule saved
	 * with a UTM condition in its set.
	 *
	 * @param array $config The condition configuration.
	 *
	 * @return array<int, string>
	 */
	private static function unit_values( array $config ): array {
		$units = $config['units'] ?? [];

		if ( is_callable( $units ) ) {
			$units = call_user_func( $units );
		}

		return is_array( $units ) ? array_map( 'strval', array_column( $units, 'value' ) ) : [];
	}

	/**
	 * Check if a value should be considered empty for a given type.
	 *
	 * This allows 0 and '0' as valid values while rejecting truly empty values.
	 * Boolean types don't require a value (they use operator only).
	 *
	 * @param mixed  $value The value to check.
	 * @param string $type  The condition type.
	 *
	 * @return bool True if the value is empty and should be skipped.
	 */
	public static function is_empty( mixed $value, string $type = 'text' ): bool {
		// Boolean types don't require a value
		if ( $type === 'boolean' ) {
			return false;
		}

		if ( is_null( $value ) ) {
			return true;
		}

		if ( $value === '' ) {
			return true;
		}

		// Empty array is empty (covers email, tags, ip, multi-select)
		if ( is_array( $value ) && empty( $value ) ) {
			return true;
		}

		// Handle number_unit type - check if number part is empty
		if ( is_array( $value ) && array_key_exists( 'number', $value ) ) {
			if ( $value['number'] === '' || $value['number'] === null ) {
				return true;
			}

			return false;
		}

		// Handle text_unit type - check if text part is empty
		if ( is_array( $value ) && array_key_exists( 'text', $value ) ) {
			if ( $value['text'] === '' || $value['text'] === null ) {
				return true;
			}

			return false;
		}

		return false;
	}
}
