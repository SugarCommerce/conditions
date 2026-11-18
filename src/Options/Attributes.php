<?php
/**
 * Attributes Options Helper
 *
 * Provides standardized product/item attribute options for select fields.
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
 * Class Attributes
 *
 * Standardized attribute definitions for condition fields.
 */
final class Attributes {

	/**
	 * Get clothing/product sizes.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_sizes(): array {
		return [
			[
				'value' => 'xs',
				'label' => __( 'Extra Small (XS)', 'sugarcommerce' ),
			],
			[
				'value' => 's',
				'label' => __( 'Small (S)', 'sugarcommerce' ),
			],
			[
				'value' => 'm',
				'label' => __( 'Medium (M)', 'sugarcommerce' ),
			],
			[
				'value' => 'l',
				'label' => __( 'Large (L)', 'sugarcommerce' ),
			],
			[
				'value' => 'xl',
				'label' => __( 'Extra Large (XL)', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Get extended clothing/product sizes.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_sizes_extended(): array {
		return [
			[
				'value' => 'xxs',
				'label' => __( 'Extra Extra Small (XXS)', 'sugarcommerce' ),
			],
			[
				'value' => 'xs',
				'label' => __( 'Extra Small (XS)', 'sugarcommerce' ),
			],
			[
				'value' => 's',
				'label' => __( 'Small (S)', 'sugarcommerce' ),
			],
			[
				'value' => 'm',
				'label' => __( 'Medium (M)', 'sugarcommerce' ),
			],
			[
				'value' => 'l',
				'label' => __( 'Large (L)', 'sugarcommerce' ),
			],
			[
				'value' => 'xl',
				'label' => __( 'Extra Large (XL)', 'sugarcommerce' ),
			],
			[
				'value' => 'xxl',
				'label' => __( 'Extra Extra Large (XXL)', 'sugarcommerce' ),
			],
			[
				'value' => 'xxxl',
				'label' => __( 'Extra Extra Extra Large (XXXL)', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Get product condition options.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_conditions(): array {
		return [
			[
				'value' => 'new',
				'label' => __( 'New', 'sugarcommerce' ),
			],
			[
				'value' => 'like_new',
				'label' => __( 'Like New', 'sugarcommerce' ),
			],
			[
				'value' => 'refurbished',
				'label' => __( 'Refurbished', 'sugarcommerce' ),
			],
			[
				'value' => 'used',
				'label' => __( 'Used', 'sugarcommerce' ),
			],
		];
	}
}
