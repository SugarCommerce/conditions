<?php
/**
 * Time Periods Options Helper
 *
 * Provides standardized time period units and options for condition fields.
 *
 * @package     SugarCommerce\Conditions\Options
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Options;

/**
 * Class Periods
 *
 * Standardized time period definitions for number_unit fields and select options.
 */
final class Periods {

	/** Time Units ****************************************************************/

	/**
	 * The window a velocity rule counts within: the last one of each unit.
	 *
	 * Used for conditions like "Orders in last X hours/days/weeks".
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_window_units(): array {
		$windows = [
			'minute'     => __( 'in the last minute', 'sugarcommerce' ),
			'5_minutes'  => __( 'in the last 5 minutes', 'sugarcommerce' ),
			'10_minutes' => __( 'in the last 10 minutes', 'sugarcommerce' ),
			'15_minutes' => __( 'in the last 15 minutes', 'sugarcommerce' ),
			'30_minutes' => __( 'in the last 30 minutes', 'sugarcommerce' ),
			'hour'       => __( 'in the last hour', 'sugarcommerce' ),
			'6_hours'    => __( 'in the last 6 hours', 'sugarcommerce' ),
			'12_hours'   => __( 'in the last 12 hours', 'sugarcommerce' ),
			'day'        => __( 'in the last 24 hours', 'sugarcommerce' ),
			'week'       => __( 'in the last 7 days', 'sugarcommerce' ),
			'month'      => __( 'in the last 30 days', 'sugarcommerce' ),
			'year'       => __( 'in the last year', 'sugarcommerce' ),
		];

		$options = [];

		foreach ( $windows as $value => $label ) {
			$options[] = [
				'value' => $value,
				'label' => $label,
			];
		}

		return $options;
	}

	/**
	 * Get time unit options.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_units(): array {
		return [
			[
				'value' => 'minute',
				'label' => __( 'Minute(s)', 'sugarcommerce' ),
			],
			[
				'value' => 'hour',
				'label' => __( 'Hour(s)', 'sugarcommerce' ),
			],
			[
				'value' => 'day',
				'label' => __( 'Day(s)', 'sugarcommerce' ),
			],
			[
				'value' => 'week',
				'label' => __( 'Week(s)', 'sugarcommerce' ),
			],
			[
				'value' => 'month',
				'label' => __( 'Month(s)', 'sugarcommerce' ),
			],
			[
				'value' => 'year',
				'label' => __( 'Year(s)', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Get age units (excludes minutes/hours).
	 *
	 * Used for conditions like "Account age > X days".
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_age_units(): array {
		return [
			[
				'value' => 'day',
				'label' => __( 'Day(s)', 'sugarcommerce' ),
			],
			[
				'value' => 'week',
				'label' => __( 'Week(s)', 'sugarcommerce' ),
			],
			[
				'value' => 'month',
				'label' => __( 'Month(s)', 'sugarcommerce' ),
			],
			[
				'value' => 'year',
				'label' => __( 'Year(s)', 'sugarcommerce' ),
			],
		];
	}

	/** Billing/Subscription Periods **********************************************/

	/**
	 * Get billing period options.
	 *
	 * Used for subscription/recurring billing cycle conditions.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_billing_periods(): array {
		return [
			[
				'value' => 'day',
				'label' => __( 'Daily', 'sugarcommerce' ),
			],
			[
				'value' => 'week',
				'label' => __( 'Weekly', 'sugarcommerce' ),
			],
			[
				'value' => 'month',
				'label' => __( 'Monthly', 'sugarcommerce' ),
			],
			[
				'value' => 'quarter',
				'label' => __( 'Quarterly', 'sugarcommerce' ),
			],
			[
				'value' => 'semi-year',
				'label' => __( 'Semi-Yearly', 'sugarcommerce' ),
			],
			[
				'value' => 'year',
				'label' => __( 'Yearly', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Get license expiration units.
	 *
	 * Used for software license expiration conditions.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_license_exp_units(): array {
		return [
			[
				'value' => 'days',
				'label' => __( 'Days', 'sugarcommerce' ),
			],
			[
				'value' => 'weeks',
				'label' => __( 'Weeks', 'sugarcommerce' ),
			],
			[
				'value' => 'months',
				'label' => __( 'Months', 'sugarcommerce' ),
			],
			[
				'value' => 'years',
				'label' => __( 'Years', 'sugarcommerce' ),
			],
		];
	}

	/** Calendar Options **********************************************************/

	/**
	 * Get month options.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_months(): array {
		return [
			[
				'value' => '1',
				'label' => __( 'January', 'sugarcommerce' ),
			],
			[
				'value' => '2',
				'label' => __( 'February', 'sugarcommerce' ),
			],
			[
				'value' => '3',
				'label' => __( 'March', 'sugarcommerce' ),
			],
			[
				'value' => '4',
				'label' => __( 'April', 'sugarcommerce' ),
			],
			[
				'value' => '5',
				'label' => __( 'May', 'sugarcommerce' ),
			],
			[
				'value' => '6',
				'label' => __( 'June', 'sugarcommerce' ),
			],
			[
				'value' => '7',
				'label' => __( 'July', 'sugarcommerce' ),
			],
			[
				'value' => '8',
				'label' => __( 'August', 'sugarcommerce' ),
			],
			[
				'value' => '9',
				'label' => __( 'September', 'sugarcommerce' ),
			],
			[
				'value' => '10',
				'label' => __( 'October', 'sugarcommerce' ),
			],
			[
				'value' => '11',
				'label' => __( 'November', 'sugarcommerce' ),
			],
			[
				'value' => '12',
				'label' => __( 'December', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Get quarter options.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_quarters(): array {
		return [
			[
				'value' => '1',
				'label' => __( 'Q1 (Jan - Mar)', 'sugarcommerce' ),
			],
			[
				'value' => '2',
				'label' => __( 'Q2 (Apr - Jun)', 'sugarcommerce' ),
			],
			[
				'value' => '3',
				'label' => __( 'Q3 (Jul - Sep)', 'sugarcommerce' ),
			],
			[
				'value' => '4',
				'label' => __( 'Q4 (Oct - Dec)', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Get day of week options.
	 *
	 * Values use ISO-8601 numeric representation (1 = Monday, 7 = Sunday).
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_days_of_week(): array {
		return [
			[
				'value' => '1',
				'label' => __( 'Monday', 'sugarcommerce' ),
			],
			[
				'value' => '2',
				'label' => __( 'Tuesday', 'sugarcommerce' ),
			],
			[
				'value' => '3',
				'label' => __( 'Wednesday', 'sugarcommerce' ),
			],
			[
				'value' => '4',
				'label' => __( 'Thursday', 'sugarcommerce' ),
			],
			[
				'value' => '5',
				'label' => __( 'Friday', 'sugarcommerce' ),
			],
			[
				'value' => '6',
				'label' => __( 'Saturday', 'sugarcommerce' ),
			],
			[
				'value' => '7',
				'label' => __( 'Sunday', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Get time of day options.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_times_of_day(): array {
		return [
			[
				'value' => 'early_morning',
				'label' => __( 'Early Morning (5am - 8am)', 'sugarcommerce' ),
			],
			[
				'value' => 'morning',
				'label' => __( 'Morning (8am - 12pm)', 'sugarcommerce' ),
			],
			[
				'value' => 'afternoon',
				'label' => __( 'Afternoon (12pm - 5pm)', 'sugarcommerce' ),
			],
			[
				'value' => 'evening',
				'label' => __( 'Evening (5pm - 9pm)', 'sugarcommerce' ),
			],
			[
				'value' => 'night',
				'label' => __( 'Night (9pm - 12am)', 'sugarcommerce' ),
			],
			[
				'value' => 'late_night',
				'label' => __( 'Late Night (12am - 5am)', 'sugarcommerce' ),
			],
		];
	}
}
