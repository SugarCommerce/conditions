<?php
/**
 * PHPUnit bootstrap.
 *
 * The comparison layer and the matcher's group logic decide whether a rule
 * fires, and neither needs WordPress to do it. The WordPress functions the
 * library touches are stubbed so the logic can be tested for what it is.
 * Hooks are real enough to add a filter and have it run; what WordPress was
 * asked to register is recorded for the tests that read it back.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

// The library's own folder stands in for wp-content, so assets/ has a URL.
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', dirname( __DIR__, 2 ) );
}

foreach ( [
	'MINUTE_IN_SECONDS' => 60,
	'HOUR_IN_SECONDS'   => 3600,
	'DAY_IN_SECONDS'    => 86400,
	'WEEK_IN_SECONDS'   => 604800,
	'MONTH_IN_SECONDS'  => 2592000,
	'YEAR_IN_SECONDS'   => 31536000,
] as $wpc_constant => $wpc_value ) {
	if ( ! defined( $wpc_constant ) ) {
		define( $wpc_constant, $wpc_value );
	}
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * Forget every hook, recorded call and staged answer.
 *
 * @return void
 */
function wpc_reset(): void {
	$GLOBALS['wpc_filters']    = [];
	$GLOBALS['wpc_calls']      = [];
	$GLOBALS['wpc_posts']      = [];
	$GLOBALS['wpc_post_meta']  = [];
	$GLOBALS['wpc_caps']       = [];
	$GLOBALS['wpc_last_query'] = null;
	$GLOBALS['wpc_options']    = [ 'siteurl' => 'https://shop.example' ];
}

wpc_reset();

/**
 * Note a call WordPress was asked to make.
 *
 * @param string $name The function.
 * @param array  $args Its arguments.
 *
 * @return void
 */
function wpc_record( string $name, array $args ): void {
	$GLOBALS['wpc_calls'][ $name ][] = $args;
}

function add_filter( $hook, $callback, $priority = 10, $accepted = 1 ) {
	$GLOBALS['wpc_filters'][ $hook ][] = $callback;

	return true;
}

function add_action( $hook, $callback, $priority = 10, $accepted = 1 ) {
	return add_filter( $hook, $callback, $priority, $accepted );
}

function apply_filters( $hook, $value, ...$args ) {
	foreach ( $GLOBALS['wpc_filters'][ $hook ] ?? [] as $callback ) {
		$value = $callback( $value, ...$args );
	}

	return $value;
}

function do_action( $hook, ...$args ) {
	wpc_record( 'do_action', [ $hook, ...$args ] );

	foreach ( $GLOBALS['wpc_filters'][ $hook ] ?? [] as $callback ) {
		$callback( ...$args );
	}
}

function __( $text, $domain = null ) {
	return $text;
}

function esc_html__( $text, $domain = null ) {
	return $text;
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function _n( $single, $plural, $number, $domain = null ) {
	return 1 === $number ? $single : $plural;
}

function wp_parse_args( $args, $defaults = [] ) {
	return array_merge( $defaults, (array) $args );
}

function current_time( $type = 'mysql', $gmt = 0 ) {
	return 'mysql' === $type ? gmdate( 'Y-m-d H:i:s' ) : gmdate( $type );
}

function wp_date( $format, $timestamp = null ) {
	return gmdate( $format, $timestamp ?? time() );
}

function absint( $value ) {
	return abs( (int) $value );
}

function get_option( $option, $default = false ) {
	return $GLOBALS['wpc_options'][ $option ] ?? $default;
}

function get_locale() {
	return 'en_US';
}

function get_user_locale( $user = 0 ) {
	return 'en_US';
}

function get_current_user_id() {
	return 0;
}

function get_userdata( $user_id ) {
	return false;
}

function is_super_admin( $user_id = false ) {
	return false;
}

function current_user_can( $capability, ...$args ) {
	return in_array( $capability, $GLOBALS['wpc_caps'], true );
}

function is_ssl() {
	return true;
}

function is_email( $email ) {
	return false !== filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : false;
}

function get_posts( $args = [] ) {
	$GLOBALS['wpc_last_query'] = $args;

	return $GLOBALS['wpc_posts'];
}

function get_post_meta( $post_id, $key = '', $single = false ) {
	return $GLOBALS['wpc_post_meta'][ $post_id ][ $key ] ?? ( $single ? '' : [] );
}

function get_post_stati( $args = [], $output = 'names', $operator = 'and' ) {
	return [];
}

function wp_roles() {
	return new class() {
		/**
		 * The role names.
		 *
		 * @return array<string, string>
		 */
		public function get_names(): array {
			return [
				'administrator' => 'Administrator',
				'customer'      => 'Customer',
			];
		}
	};
}

function wp_parse_url( $url, $component = -1 ) {
	return parse_url( (string) $url, $component ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
}

function esc_url_raw( $url ) {
	$url = trim( (string) $url );

	return preg_match( '#^https?://#i', $url ) ? $url : '';
}

function rest_url( $path = '' ) {
	return 'https://shop.example/wp-json/' . ltrim( (string) $path, '/' );
}

function wp_create_nonce( $action = -1 ) {
	return 'nonce:' . $action;
}

function wp_verify_nonce( $nonce, $action = -1 ) {
	return 'nonce:' . $action === $nonce ? 1 : false;
}

function wp_slash( $value ) {
	return is_array( $value ) ? array_map( 'wp_slash', $value ) : ( is_string( $value ) ? addslashes( $value ) : $value );
}

// Like core: what it is given is unslashed before it is stored.
function update_post_meta( $post_id, $key, $value ) {
	$GLOBALS['wpc_post_meta'][ $post_id ][ $key ] = wp_unslash( $value );

	return true;
}

function content_url( $path = '' ) {
	return 'https://shop.example/wp-content' . ( '' === $path ? '' : '/' . ltrim( (string) $path, '/' ) );
}

function site_url( $path = '' ) {
	return 'https://shop.example' . $path;
}

function wp_json_encode( $data, $options = 0, $depth = 512 ) {
	return json_encode( $data, $options, $depth ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
}

function post_type_exists( $post_type ) {
	return false;
}

/*
 * The sanitizers are close to core's -- close enough that a regex run
 * through sanitize_text_field() comes out mangled the way it really does.
 */
function sanitize_text_field( $str ) {
	if ( is_object( $str ) || is_array( $str ) ) {
		return '';
	}

	$str = (string) $str;

	if ( str_contains( $str, '<' ) ) {
		$str = strip_tags( $str ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
		$str = str_replace( '<', '&lt;', $str );
	}

	$str = (string) preg_replace( '/[\r\n\t ]+/', ' ', $str );
	$str = (string) preg_replace( '/%[a-f0-9]{2}/i', '', $str );

	return trim( $str );
}

function sanitize_key( $key ) {
	return is_scalar( $key ) ? strtolower( (string) preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $key ) ) : '';
}

// Deep, like core's.
function wp_unslash( $value ) {
	return is_array( $value ) ? array_map( 'wp_unslash', $value ) : ( is_string( $value ) ? stripslashes( $value ) : $value );
}

function wp_check_invalid_utf8( $text ) {
	return (string) $text;
}

// What a set asks WordPress to register, recorded rather than done.
function register_post_type( ...$args ) {
	wpc_record( __FUNCTION__, $args );
}

function register_post_meta( ...$args ) {
	wpc_record( __FUNCTION__, $args );
}

function add_meta_box( ...$args ) {
	wpc_record( __FUNCTION__, $args );
}

function register_rest_route( ...$args ) {
	wpc_record( __FUNCTION__, $args );
}

function wp_enqueue_style( ...$args ) {
	wpc_record( __FUNCTION__, $args );
}

function wp_enqueue_script( ...$args ) {
	wpc_record( __FUNCTION__, $args );
}

function wp_add_inline_script( ...$args ) {
	wpc_record( __FUNCTION__, $args );
}

/**
 * A REST request with parameters.
 */
class WP_REST_Request {

	/**
	 * The parameters.
	 *
	 * @param array $params The parameters.
	 */
	public function __construct( private array $params = [] ) {
	}

	/**
	 * One parameter.
	 *
	 * @param string $key The name.
	 *
	 * @return mixed
	 */
	public function get_param( $key ) {
		return $this->params[ $key ] ?? null;
	}
}

/**
 * A rule post: only what the matcher reads.
 */
class WP_Post {

	/**
	 * The post's fields.
	 *
	 * @param int    $ID         The id.
	 * @param string $post_title The title.
	 * @param string $post_type  The post type.
	 */
	public function __construct( public int $ID = 0, public string $post_title = '', public string $post_type = 'post' ) {
	}
}
