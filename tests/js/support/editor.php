<?php
/**
 * The builder's page as PHP draws it, for the scripts' tests.
 *
 * Run by tests/js/support/page.js: `php editor.php [saved.json]`. Registers a
 * set with one condition of every field type, draws the meta box
 * (Admin\Editor::render(), the saved groups given as JSON) and prints
 * `{ "html": …, "build": …, "config": … }`, the config being what
 * Admin\Assets hands the script and the build its key. The tests then run against the real markup and data, not a copy.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

use SugarCommerce\Conditions\Admin\Assets;
use SugarCommerce\Conditions\Admin\Editor;
use SugarCommerce\Conditions\Conditions;
use SugarCommerce\Conditions\Registry;

require dirname( __DIR__, 2 ) . '/bootstrap.php';

$units = [
	[
		'value' => 'g',
		'label' => 'grams',
	],
	[
		'value' => 'kg',
		'label' => 'kilograms',
	],
];

Conditions::use_prefix( 'shop' );
Conditions::register(
	'discount_rule',
	[
		'description' => 'Groups are ORed.',
		'conditions'  => [
			'coupon'   => [
				'label'       => 'Coupon',
				'group'       => 'Cart',
				'type'        => 'text',
				'placeholder' => 'e.g. SPRING',
				'description' => 'The coupon code.',
				'arg'         => 'coupon',
			],
			'total'    => [
				'label' => 'Cart Total',
				'group' => 'Cart',
				'type'  => 'number',
				'min'   => 0,
				'step'  => 0.01,
				'arg'   => 'total',
			],
			'weight'   => [
				'label' => 'Weight',
				'group' => 'Cart',
				'type'  => 'number_unit',
				'units' => $units,
				'arg'   => 'weight',
			],
			'gift'     => [
				'label' => 'Gift Note',
				'group' => 'Cart',
				'type'  => 'text_unit',
				'units' => $units,
				'arg'   => 'gift',
			],
			'is_gift'  => [
				'label' => 'Is a Gift',
				'group' => 'Cart',
				'type'  => 'boolean',
				'arg'   => 'is_gift',
			],
			'deliver'  => [
				'label' => 'Delivery Date',
				'group' => 'Dates',
				'type'  => 'date',
				'arg'   => 'deliver',
			],
			'pickup'   => [
				'label' => 'Pickup Time',
				'group' => 'Dates',
				'type'  => 'time',
				'arg'   => 'pickup',
			],
			'colour'   => [
				'label'   => 'Colour',
				'group'   => 'Product',
				'type'    => 'select',
				'options' => [
					'red'  => 'Red',
					'blue' => 'Blue',
				],
				'arg'     => 'colour',
			],
			'sizes'    => [
				'label'    => 'Sizes',
				'group'    => 'Product',
				'type'     => 'select',
				'multiple' => true,
				'options'  => [
					[
						'value' => 's',
						'label' => 'Small',
					],
					[
						'value' => 'm',
						'label' => 'Medium',
					],
				],
				'arg'      => 'sizes',
			],
			'tags'     => [
				'label' => 'Order Tags',
				'group' => 'Order',
				'type'  => 'tags',
				'arg'   => 'tags',
			],
			'ip'       => [
				'label' => 'IP',
				'group' => 'Visitor',
				'type'  => 'ip',
				'arg'   => 'ip',
			],
			'email'    => [
				'label' => 'Email',
				'group' => 'Visitor',
				'type'  => 'email',
				'arg'   => 'email',
			],
			'page'     => [
				'label'     => 'Landing Page',
				'group'     => 'Content',
				'type'      => 'post',
				'post_type' => 'page',
				'multiple'  => true,
				'arg'       => 'page',
			],
			'category' => [
				'label'    => 'Category',
				'group'    => 'Content',
				'type'     => 'term',
				'taxonomy' => 'category',
				'arg'      => 'category',
			],
			'manager'  => [
				'label'    => 'Manager',
				'group'    => 'People',
				'type'     => 'user',
				'role'     => [ 'administrator', 'editor' ],
				'multiple' => true,
				'arg'      => 'manager',
			],
			'customer' => [
				'label'    => 'Customer',
				'group'    => 'People',
				'type'     => 'ajax',
				'multiple' => true,
				'ajax'     => static fn(): array => [],
				'arg'      => 'customer',
			],
		],
	]
);

$saved = isset( $argv[1] ) ? json_decode( (string) file_get_contents( $argv[1] ), true ) : [];

$GLOBALS['wpc_post_meta'][7][ Registry::META_KEY ] = $saved;

ob_start();
Editor::render( new WP_Post( 7, 'A rule', 'discount_rule' ), [ 'args' => [ 'set_id' => 'discount_rule' ] ] );
$html = (string) ob_get_clean();

echo json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- printed for a test, not WordPress.
	[
		'html'   => $html,
		'build'  => Conditions::handle( 'admin' ),
		'config' => ( new Assets() )->get_localized_data( 'discount_rule' ),
	]
);
