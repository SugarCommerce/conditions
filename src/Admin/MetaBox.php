<?php
/**
 * Meta Box Registration
 *
 * Handles meta box registration for condition sets.
 *
 * @package     SugarCommerce\Conditions\Admin
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Admin;

use SugarCommerce\Conditions\Registry;
use WP_Post;

/**
 * Class MetaBox
 *
 * Manages meta box registration for condition sets.
 */
final class MetaBox {

	/**
	 * The condition set ID.
	 *
	 * @var string
	 */
	private string $set_id;

	/**
	 * The condition set configuration.
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Constructor.
	 *
	 * @param string $set_id The condition set ID.
	 * @param array  $config The condition set configuration.
	 */
	public function __construct( string $set_id, array $config ) {
		$this->set_id = $set_id;
		$this->config = $config;
	}

	/**
	 * Register the meta box.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post', [ $this, 'save_meta' ], 10, 2 );

		// Register the post meta
		$this->register_meta();
	}

	/**
	 * Add the meta box.
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		$title = $this->config['metabox_title'] ?? __( 'Conditions', 'sugarcommerce' );

		/**
		 * Filter the meta box title.
		 *
		 * @param string $title  The meta box title.
		 * @param string $set_id The condition set ID.
		 * @param array  $config The condition set configuration.
		 */
		$title = apply_filters( "{$this->set_id}_metabox_title", $title, $this->set_id, $this->config );

		add_meta_box(
			$this->set_id . '_conditions',
			$title,
			[ Editor::class, 'render' ],
			$this->set_id,
			'normal',
			'high',
			[ 'set_id' => $this->set_id ]
		);
	}

	/**
	 * Register post meta for conditions.
	 *
	 * @return void
	 */
	private function register_meta(): void {
		register_post_meta( $this->set_id, Registry::META_KEY, [
			'type'          => 'array',
			'single'        => true,
			'show_in_rest'  => false,
			'auth_callback' => function ( $allowed, $meta_key, $post_id ) {
				$capability = $this->config['capability'] ?? 'manage_options';

				return current_user_can( $capability, $post_id );
			},
		] );
	}

	/**
	 * Save conditions meta from form submission.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function save_meta( int $post_id, WP_Post $post ): void {
		// Check if this is our CPT
		if ( $post->post_type !== $this->set_id ) {
			return;
		}

		// Verify nonce. The action names the set, so a form for one set
		// cannot write another set's rules.
		if ( ! isset( $_POST['conditions_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['conditions_nonce'] ) ), Editor::nonce_action( $this->set_id ) ) ) {
			return;
		}

		// Don't save on autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		$capability = $this->config['capability'] ?? 'manage_options';
		if ( ! current_user_can( $capability, $post_id ) ) {
			return;
		}

		// Unslash before sanitizing, not after. WordPress slashes every value in
		// $_POST, and these are rule values an admin typed: a regex condition
		// like /^buyer\@example\.com$/ comes back with its backslashes doubled
		// and gains another pair on every re-save, until the pattern no longer
		// matches anything. Values with an apostrophe have the same problem.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitizer::sanitize_conditions() handles this, per-condition.
		$raw_conditions = isset( $_POST['_conditions'] ) ? wp_unslash( $_POST['_conditions'] ) : [];

		// Get condition configurations for custom sanitization
		$condition_configs = Registry::get_conditions_raw( $this->set_id );

		// Sanitize with condition configs for custom sanitizers
		$conditions = Sanitizer::sanitize_conditions( $raw_conditions, $condition_configs );

		/**
		 * Filter the conditions before saving.
		 *
		 * @param array   $conditions The sanitized conditions.
		 * @param int     $post_id    The post ID.
		 * @param WP_Post $post       The post object.
		 * @param string  $set_id     The condition set ID.
		 */
		$conditions = apply_filters( "{$this->set_id}_save_conditions", $conditions, $post_id, $post, $this->set_id );

		// Save
		// Slashed again for update_post_meta(), which unslashes what it is
		// given: without this the backslashes in a regex rule were stripped a
		// second time on the way into the database.
		update_post_meta( $post_id, Registry::META_KEY, wp_slash( $conditions ) );

		/**
		 * Action fired after conditions are saved.
		 *
		 * @param int     $post_id    The post ID.
		 * @param array   $conditions The saved conditions.
		 * @param WP_Post $post       The post object.
		 * @param string  $set_id     The condition set ID.
		 */
		do_action( "{$this->set_id}_conditions_saved", $post_id, $conditions, $post, $this->set_id );
	}
}
