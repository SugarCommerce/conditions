<?php
/**
 * Registering
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Traits\Registry;

use InvalidArgumentException;
use SugarCommerce\Conditions\Admin\MetaBox;
use SugarCommerce\Conditions\Admin\PostType;
use SugarCommerce\Conditions\Builtin;
use SugarCommerce\Conditions\Condition;
use SugarCommerce\Conditions\Conditions;

/**
 * Registering a set of conditions and each condition in it, normalised,
 * with the queries its pickers may make.
 */
trait Registration {

	/**
	 * Register a condition set.
	 *
	 * Creates a custom post type for storing rules with this condition set.
	 *
	 * @param string $set_id        Unique identifier for the condition set.
	 * @param array  $args          {
	 *                              Configuration arguments.
	 *
	 * @type array   $labels        Labels for the CPT (singular, plural).
	 * @type string  $menu_icon     Dashicon or URL for menu icon.
	 * @type string  $menu_parent   Parent menu slug to nest under.
	 * @type string  $parent_file   Parent file for menu highlighting.
	 * @type string  $submenu_file  Submenu file for menu highlighting.
	 * @type string  $capability    Required capability to manage.
	 * @type array   $conditions    Array of conditions to register.
	 * @type array   $redirect      Redirect configuration (after_trash, show_undo).
	 * @type string  $description   Meta box description text.
	 * @type string  $metabox_title Custom meta box title.
	 * @type array   $supports      Post type supports array.
	 * @type bool    $show_ui       Whether the edit screens exist (default true).
	 * @type bool    $show_in_menu  Whether the post type has a menu item.
	 *                              }
	 *
	 * @return void
	 * @throws InvalidArgumentException If set_id is empty or already registered.
	 */
	public static function register_set( string $set_id, array $args = [] ): void {
		if ( empty( $set_id ) ) {
			throw new InvalidArgumentException( 'Condition set ID cannot be empty.' );
		}

		if ( isset( self::$sets[ $set_id ] ) ) {
			throw new InvalidArgumentException( sprintf( 'Condition set "%s" is already registered.', esc_html( $set_id ) ) );
		}

		// Ensure library is initialized
		self::init();

		// Parse defaults
		$defaults = [
			'labels'        => [
				'singular' => ucwords( str_replace( [ '_', '-' ], ' ', $set_id ) ),
				'plural'   => ucwords( str_replace( [ '_', '-' ], ' ', $set_id ) ) . 's',
			],
			'menu_icon'     => 'dashicons-yes-alt',
			'menu_parent'   => null,
			'parent_file'   => null,
			'submenu_file'  => null,
			'show_in_menu'  => true,
			'capability'    => 'manage_options',
			'conditions'    => [],
			'redirect'      => [],
			'description'   => __( 'Configure when this rule should apply. Groups are connected with OR logic, conditions within a group use AND logic.', 'sugarcommerce' ),
			'show_ui'       => true,
			'metabox_title' => null,
			// page-attributes is the Order box. Rules are checked in
			// menu_order, and without the box nothing could set one.
			'supports'      => [ 'title', 'page-attributes' ],
		];

		$args = wp_parse_args( $args, $defaults );

		// wp_parse_args() is shallow: a caller giving only a singular label
		// lost the plural, and the post type registered with a null label.
		$args['labels'] = wp_parse_args( (array) $args['labels'], $defaults['labels'] );

		self::$sets[ $set_id ] = $args;

		// Initialize conditions array for this set
		if ( ! isset( self::$conditions[ $set_id ] ) ) {
			self::$conditions[ $set_id ] = [];
		}

		// Register conditions if provided
		if ( ! empty( $args['conditions'] ) ) {
			self::register_conditions_array( $set_id, $args['conditions'] );
		}

		// Register the post type
		self::$post_types[ $set_id ] = new PostType( $set_id, $args );
		self::$post_types[ $set_id ]->register();

		// Register the meta box
		self::$meta_boxes[ $set_id ] = new MetaBox( $set_id, $args );
		self::$meta_boxes[ $set_id ]->register();

		// Add to assets tracking
		self::$assets?->add_set_id( $set_id );

		/**
		 * Action fired after a condition set is registered:
		 * `{prefix}_conditions_set_registered`.
		 *
		 * @param string $set_id The condition set ID.
		 * @param array  $args   The condition set configuration.
		 */
		do_action( Conditions::hook( 'set_registered' ), $set_id, $args );
	}

	/**
	 * Register a single condition to a set.
	 *
	 * @param string $set_id       The condition set ID.
	 * @param string $condition_id Condition ID, class name, or built-in name.
	 * @param array  $args         Condition configuration (if not a class).
	 *
	 * @return void
	 * @throws InvalidArgumentException If the condition is not a class, a built-in or a configuration.
	 */
	public static function register_condition( string $set_id, string $condition_id, array $args = [] ): void {
		// Ensure set exists
		if ( ! isset( self::$sets[ $set_id ] ) ) {
			// Auto-create set with defaults if it doesn't exist
			self::register_set( $set_id );
		}

		if ( '' === $condition_id ) {
			throw new InvalidArgumentException( 'Condition ID cannot be empty.' );
		}

		// Class-based. Checked before it is built: constructing any class a
		// caller named, to find out afterwards it was not a condition, ran
		// that class's constructor for nothing.
		if ( class_exists( $condition_id ) ) {
			if ( ! is_subclass_of( $condition_id, Condition::class ) ) {
				throw new InvalidArgumentException(
					sprintf( 'Class "%s" must extend %s.', esc_html( $condition_id ), Condition::class )
				);
			}

			$instance                                       = new $condition_id();
			$config                                         = $instance->to_array();
			self::$conditions[ $set_id ][ $config['name'] ] = $config;
			self::update_allowed_queries( $config );

			return;
		}

		// Handle built-in condition references
		if ( empty( $args ) ) {
			$built_in = Builtin::get( $condition_id );

			if ( $built_in ) {
				self::$conditions[ $set_id ][ $condition_id ] = $built_in;
				self::update_allowed_queries( $built_in );

				return;
			}

			throw new InvalidArgumentException(
				sprintf( 'Built-in condition "%s" not found.', esc_html( $condition_id ) )
			);
		}

		// Config-based.
		$config = self::normalize_condition_config( $condition_id, $args );

		self::$conditions[ $set_id ][ $condition_id ] = $config;
		self::update_allowed_queries( $config );
	}

	/**
	 * Register multiple conditions from an array.
	 *
	 * @param string $set_id     The condition set ID.
	 * @param array  $conditions Array of conditions.
	 *
	 * @return void
	 */
	private static function register_conditions_array( string $set_id, array $conditions ): void {
		foreach ( $conditions as $key => $value ) {
			// Class-based: MyCondition::class
			if ( is_string( $value ) && class_exists( $value ) ) {
				self::register_condition( $set_id, $value );
				continue;
			}

			// Built-in reference: 'user_role'
			if ( is_string( $value ) && is_int( $key ) ) {
				self::register_condition( $set_id, $value );
				continue;
			}

			// Config-based: 'my_condition' => [...]
			if ( is_string( $key ) && is_array( $value ) ) {
				self::register_condition( $set_id, $key, $value );
			}
		}
	}

	/**
	 * Normalize a condition configuration array.
	 *
	 * @param string $condition_id The condition ID.
	 * @param array  $args         The condition arguments.
	 *
	 * @return array Normalized configuration.
	 */
	private static function normalize_condition_config( string $condition_id, array $args ): array {
		return wp_parse_args( $args, [
			'name'          => $condition_id,
			'label'         => ucwords( str_replace( [ '_', '-' ], ' ', $condition_id ) ),
			'group'         => 'General',
			'type'          => 'text',
			'multiple'      => false,
			'options'       => [],
			'units'         => [],
			'operators'     => null,
			'arg'           => null,
			'compare_value' => null,
			'required_args' => [],
			'post_type'     => null,
			'taxonomy'      => null,
			'role'          => null,
		] );
	}

	/**
	 * Update allowed queries based on condition config.
	 *
	 * @param array $config Condition configuration.
	 *
	 * @return void
	 */
	private static function update_allowed_queries( array $config ): void {
		$type = $config['type'] ?? '';

		if ( $type === 'post' && ! empty( $config['post_type'] ) ) {
			$post_types               = (array) $config['post_type'];
			self::$allowed_post_types = array_unique(
				array_merge( self::$allowed_post_types, $post_types )
			);
		}

		if ( $type === 'term' && ! empty( $config['taxonomy'] ) ) {
			$taxonomies               = (array) $config['taxonomy'];
			self::$allowed_taxonomies = array_unique(
				array_merge( self::$allowed_taxonomies, $taxonomies )
			);
		}

		if ( $type === 'user' && ! empty( $config['role'] ) ) {
			$roles               = (array) $config['role'];
			self::$allowed_roles = array_unique(
				array_merge( self::$allowed_roles, $roles )
			);
		}
	}
}
