<?php
/**
 * DateTime Built-in Conditions
 *
 * @package     SugarCommerce\Conditions\Builtin
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Builtin;

use SugarCommerce\Conditions\Helpers\DateTime as DateTimeHelper;
use SugarCommerce\Conditions\Options\Periods;
use SugarCommerce\Conditions\Operators;

/**
 * Class DateTime
 *
 * Provides date and time related conditions.
 */
final class DateTime {

	/**
	 * Get all datetime conditions.
	 *
	 * @return array<string, array>
	 */
	public static function get_all(): array {
		return [
			'current_date'      => [
				'label'         => __( 'Date', 'sugarcommerce' ),
				'group'         => __( 'Date & Time', 'sugarcommerce' ),
				'type'          => 'date',
				'description'   => __( 'Match against the current date.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => DateTimeHelper::get_current_date( $args ),
				'required_args' => [],
			],
			'current_time'      => [
				'label'         => __( 'Time', 'sugarcommerce' ),
				'group'         => __( 'Date & Time', 'sugarcommerce' ),
				'type'          => 'time',
				'description'   => __( 'Match against the current time.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => DateTimeHelper::get_current_time( $args ),
				'required_args' => [],
			],
			'current_year'      => [
				'label'         => __( 'Year', 'sugarcommerce' ),
				'group'         => __( 'Date & Time', 'sugarcommerce' ),
				'type'          => 'number',
				'placeholder'   => __( 'e.g. 2026', 'sugarcommerce' ),
				'description'   => __( 'Match against the current year.', 'sugarcommerce' ),
				'min'           => 2000,
				'max'           => 2100,
				'step'          => 1,
				'compare_value' => fn( $args ) => DateTimeHelper::get_current_year( $args ),
				'required_args' => [],
			],
			'current_month'     => [
				'label'         => __( 'Month', 'sugarcommerce' ),
				'group'         => __( 'Date & Time', 'sugarcommerce' ),
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select months...', 'sugarcommerce' ),
				'description'   => __( 'Match against the current month.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => Periods::get_months(),
				'compare_value' => fn( $args ) => DateTimeHelper::get_current_month( $args ),
				'required_args' => [],
			],
			'quarter'           => [
				'label'         => __( 'Quarter', 'sugarcommerce' ),
				'group'         => __( 'Date & Time', 'sugarcommerce' ),
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select quarters...', 'sugarcommerce' ),
				'description'   => __( 'Match against the current quarter.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => Periods::get_quarters(),
				'compare_value' => fn( $args ) => DateTimeHelper::get_current_quarter( $args ),
				'required_args' => [],
			],
			'week_of_year'      => [
				'label'         => __( 'Week of Year', 'sugarcommerce' ),
				'group'         => __( 'Date & Time', 'sugarcommerce' ),
				'type'          => 'number',
				'placeholder'   => __( 'e.g. 1-52', 'sugarcommerce' ),
				'description'   => __( 'Match against the current week number (1-53).', 'sugarcommerce' ),
				'min'           => 1,
				'max'           => 53,
				'step'          => 1,
				'compare_value' => fn( $args ) => DateTimeHelper::get_current_week( $args ),
				'required_args' => [],
			],
			'day_of_week'       => [
				'label'         => __( 'Day of Week', 'sugarcommerce' ),
				'group'         => __( 'Date & Time', 'sugarcommerce' ),
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select days...', 'sugarcommerce' ),
				'description'   => __( 'Match against the current day of the week.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => Periods::get_days_of_week(),
				'compare_value' => fn( $args ) => DateTimeHelper::get_day_of_week( $args ),
				'required_args' => [],
			],
			'day_of_month'      => [
				'label'         => __( 'Day of Month', 'sugarcommerce' ),
				'group'         => __( 'Date & Time', 'sugarcommerce' ),
				'type'          => 'number',
				'placeholder'   => __( 'e.g. 1-31', 'sugarcommerce' ),
				'description'   => __( 'Match against the current day of the month (1-31).', 'sugarcommerce' ),
				'min'           => 1,
				'max'           => 31,
				'step'          => 1,
				'compare_value' => fn( $args ) => DateTimeHelper::get_day_of_month( $args ),
				'required_args' => [],
			],
			'day_of_year'       => [
				'label'         => __( 'Day of Year', 'sugarcommerce' ),
				'group'         => __( 'Date & Time', 'sugarcommerce' ),
				'type'          => 'number',
				'placeholder'   => __( 'e.g. 1-365', 'sugarcommerce' ),
				'description'   => __( 'Match against the current day of the year (1-365).', 'sugarcommerce' ),
				'min'           => 1,
				'max'           => 366,
				'step'          => 1,
				'compare_value' => fn( $args ) => DateTimeHelper::get_day_of_year( $args ),
				'required_args' => [],
			],
			'time_of_day'       => [
				'label'         => __( 'Time of Day', 'sugarcommerce' ),
				'group'         => __( 'Date & Time', 'sugarcommerce' ),
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select time of day...', 'sugarcommerce' ),
				'description'   => __( 'Match against the current part of the day.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => Periods::get_times_of_day(),
				'compare_value' => fn( $args ) => DateTimeHelper::get_time_of_day( $args ),
				'required_args' => [],
			],
			'is_weekend'        => [
				'label'         => __( 'Weekend', 'sugarcommerce' ),
				'group'         => __( 'Date & Time', 'sugarcommerce' ),
				'type'          => 'boolean',
				'description'   => __( 'Check if today is Saturday or Sunday.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => DateTimeHelper::is_weekend( $args ),
				'required_args' => [],
			],
			'is_weekday'        => [
				'label'         => __( 'Weekday', 'sugarcommerce' ),
				'group'         => __( 'Date & Time', 'sugarcommerce' ),
				'type'          => 'boolean',
				'description'   => __( 'Check if today is Monday through Friday.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => DateTimeHelper::is_weekday( $args ),
				'required_args' => [],
			],
			'is_business_hours' => [
				'label'         => __( 'Business Hours', 'sugarcommerce' ),
				'group'         => __( 'Date & Time', 'sugarcommerce' ),
				'type'          => 'boolean',
				'description'   => __( 'Check if current time is Monday-Friday 9am-5pm.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => DateTimeHelper::is_business_hours( $args ),
				'required_args' => [],
			],
		];
	}
}
