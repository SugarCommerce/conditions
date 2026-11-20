<?php
/**
 * Operators::get_all() and Operators::for_type() for every field type, for
 * the scripts' tests: the builder must offer what the matcher understands.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

use SugarCommerce\Conditions\Operators;

require dirname( __DIR__, 2 ) . '/bootstrap.php';

$types = [ 'text', 'number', 'number_unit', 'text_unit', 'boolean', 'date', 'time', 'ip', 'email', 'tags', 'select', 'post', 'term', 'user', 'ajax', 'something_new' ];

$by_type = [];

foreach ( $types as $type ) {
	$by_type[ $type ] = [
		'single'   => Operators::for_type( $type ),
		'multiple' => Operators::for_type( $type, true ),
	];
}

echo json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- printed for a test, not WordPress.
	[
		'all'    => Operators::get_all(),
		'byType' => $by_type,
	]
);
