<?php
/**
 * REST Routes
 *
 * The search endpoints behind the editor's post, term, user and AJAX pickers.
 *
 * @package     SugarCommerce\Conditions\Rest
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Rest;

use SugarCommerce\Conditions\Conditions;
use SugarCommerce\Conditions\Registry;
use WP_REST_Request;

/**
 * Class Routes
 *
 * Registers the routes under this copy's namespace (see Conditions::use_prefix()).
 */
final class Routes {

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register all REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->register_posts_route();
		$this->register_terms_route();
		$this->register_users_route();
		$this->register_ajax_route();
	}

	/**
	 * Register posts endpoint.
	 *
	 * @return void
	 */
	private function register_posts_route(): void {
		register_rest_route( Conditions::rest_namespace(), '/posts', [
			'methods'             => 'GET',
			'callback'            => [ Posts::class, 'search' ],
			'permission_callback' => [ $this, 'permission_check' ],
			'args'                => [
				'post_type' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				],
				'search'    => [
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'include'   => [
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );
	}

	/**
	 * Register terms endpoint.
	 *
	 * @return void
	 */
	private function register_terms_route(): void {
		register_rest_route( Conditions::rest_namespace(), '/terms', [
			'methods'             => 'GET',
			'callback'            => [ Terms::class, 'search' ],
			'permission_callback' => [ $this, 'permission_check' ],
			'args'                => [
				'taxonomy' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				],
				'search'   => [
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'include'  => [
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );
	}

	/**
	 * Register users endpoint.
	 *
	 * @return void
	 */
	private function register_users_route(): void {
		register_rest_route( Conditions::rest_namespace(), '/users', [
			'methods'             => 'GET',
			'callback'            => [ Users::class, 'search' ],
			'permission_callback' => [ $this, 'permission_check' ],
			'args'                => [
				'role'    => [
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'search'  => [
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'include' => [
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );
	}

	/**
	 * Register custom AJAX endpoint for type => 'ajax' conditions.
	 *
	 * @return void
	 */
	private function register_ajax_route(): void {
		register_rest_route( Conditions::rest_namespace(), '/ajax', [
			'methods'             => 'GET',
			'callback'            => [ Ajax::class, 'handle' ],
			'permission_callback' => [ $this, 'permission_check' ],
			'args'                => [
				'set_id'       => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				],
				'condition_id' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'search'       => [
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'include'      => [
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );
	}

	/**
	 * Whether the current user may search.
	 *
	 * A request naming a set needs that set's capability. One that does not
	 * -- the post, term and user pickers -- needs the capability of a set
	 * whose editor could have asked: a set registered with a shop manager's
	 * capability used to have its pickers refused, because every route
	 * demanded manage_options whatever the set said.
	 *
	 * `{prefix}_conditions_rest_permission` has the last word.
	 *
	 * @param WP_REST_Request|null $request The request.
	 *
	 * @return bool
	 */
	public function permission_check( ?WP_REST_Request $request = null ): bool {
		$set_id = $request ? (string) $request->get_param( 'set_id' ) : '';
		$sets   = '' !== $set_id ? array_filter( [ $set_id => Registry::get_set( $set_id ) ] ) : Registry::get_sets();
		$allow  = false;

		foreach ( $sets as $set ) {
			if ( current_user_can( (string) ( $set['capability'] ?? 'manage_options' ) ) ) {
				$allow = true;
				break;
			}
		}

		/**
		 * Filter whether the current user may use the search endpoints.
		 *
		 * @param bool                 $allow   Whether they may.
		 * @param WP_REST_Request|null $request The request.
		 */
		return (bool) apply_filters( Conditions::hook( 'rest_permission' ), $allow, $request );
	}

	/**
	 * Get the REST namespace.
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return Conditions::rest_namespace();
	}
}
