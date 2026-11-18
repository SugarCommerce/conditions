<?php
/**
 * Core's get_post_timestamp(), answering two hours and a minute ago.
 *
 * Loaded only in a process of its own, so the rest of the suite runs the
 * library's fallback.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

function get_post_timestamp( $post = null, $field = 'date' ) {
	wpc_record( 'get_post_timestamp', [ $post, $field ] );

	return time() - 2 * 3600 - 60;
}
