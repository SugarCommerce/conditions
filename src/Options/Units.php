<?php
/**
 * Units Options Helper
 *
 * Provides standardized unit definitions for number_unit fields.
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
 * Class Units
 *
 * Standardized unit definitions for number_unit condition fields.
 */
final class Units {

	/** Measurement Units *********************************************************/

	/**
	 * Get weight units.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_weight(): array {
		return [
			[
				'value' => 'g',
				'label' => __( 'Grams (g)', 'sugarcommerce' ),
			],
			[
				'value' => 'kg',
				'label' => __( 'Kilograms (kg)', 'sugarcommerce' ),
			],
			[
				'value' => 'oz',
				'label' => __( 'Ounces (oz)', 'sugarcommerce' ),
			],
			[
				'value' => 'lb',
				'label' => __( 'Pounds (lb)', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Get length/dimension units.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_length(): array {
		return [
			[
				'value' => 'mm',
				'label' => __( 'Millimeters (mm)', 'sugarcommerce' ),
			],
			[
				'value' => 'cm',
				'label' => __( 'Centimeters (cm)', 'sugarcommerce' ),
			],
			[
				'value' => 'm',
				'label' => __( 'Meters (m)', 'sugarcommerce' ),
			],
			[
				'value' => 'in',
				'label' => __( 'Inches (in)', 'sugarcommerce' ),
			],
			[
				'value' => 'ft',
				'label' => __( 'Feet (ft)', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Get distance units.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_distance(): array {
		return [
			[
				'value' => 'm',
				'label' => __( 'Meters (m)', 'sugarcommerce' ),
			],
			[
				'value' => 'km',
				'label' => __( 'Kilometers (km)', 'sugarcommerce' ),
			],
			[
				'value' => 'mi',
				'label' => __( 'Miles (mi)', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Get volume units.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_volume(): array {
		return [
			[
				'value' => 'ml',
				'label' => __( 'Milliliters (ml)', 'sugarcommerce' ),
			],
			[
				'value' => 'l',
				'label' => __( 'Liters (L)', 'sugarcommerce' ),
			],
			[
				'value' => 'fl_oz',
				'label' => __( 'Fluid Ounces (fl oz)', 'sugarcommerce' ),
			],
			[
				'value' => 'gal',
				'label' => __( 'Gallons (gal)', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Get temperature units.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_temperature(): array {
		return [
			[
				'value' => 'c',
				'label' => __( 'Celsius (°C)', 'sugarcommerce' ),
			],
			[
				'value' => 'f',
				'label' => __( 'Fahrenheit (°F)', 'sugarcommerce' ),
			],
		];
	}

	/** Digital Units *************************************************************/

	/**
	 * Get data storage units.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_data(): array {
		return [
			[
				'value' => 'kb',
				'label' => __( 'Kilobytes (KB)', 'sugarcommerce' ),
			],
			[
				'value' => 'mb',
				'label' => __( 'Megabytes (MB)', 'sugarcommerce' ),
			],
			[
				'value' => 'gb',
				'label' => __( 'Gigabytes (GB)', 'sugarcommerce' ),
			],
			[
				'value' => 'tb',
				'label' => __( 'Terabytes (TB)', 'sugarcommerce' ),
			],
		];
	}

	/** Quantity Units ************************************************************/

	/**
	 * Get quantity/count units.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_quantity(): array {
		return [
			[
				'value' => 'unit',
				'label' => __( 'Units', 'sugarcommerce' ),
			],
			[
				'value' => 'piece',
				'label' => __( 'Pieces', 'sugarcommerce' ),
			],
			[
				'value' => 'item',
				'label' => __( 'Items', 'sugarcommerce' ),
			],
		];
	}

	/** Currency Units ************************************************************/

	/**
	 * Get currency units (common).
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_currency(): array {
		return [
			[
				'value' => 'usd',
				'label' => __( 'US Dollars ($)', 'sugarcommerce' ),
			],
			[
				'value' => 'eur',
				'label' => __( 'Euros (€)', 'sugarcommerce' ),
			],
			[
				'value' => 'gbp',
				'label' => __( 'British Pounds (£)', 'sugarcommerce' ),
			],
			[
				'value' => 'cad',
				'label' => __( 'Canadian Dollars (C$)', 'sugarcommerce' ),
			],
			[
				'value' => 'aud',
				'label' => __( 'Australian Dollars (A$)', 'sugarcommerce' ),
			],
		];
	}
}
