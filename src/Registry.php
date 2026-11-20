<?php
/**
 * Conditions Registry
 *
 * Central registry for all condition sets and their conditions.
 * Handles registration, storage, and retrieval of conditions.
 *
 * @package     SugarCommerce\Conditions
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions;

use SugarCommerce\Conditions\Admin\Assets;
use SugarCommerce\Conditions\Admin\MetaBox;
use SugarCommerce\Conditions\Admin\PostType;
use SugarCommerce\Conditions\Rest\Routes;
use SugarCommerce\Conditions\Traits\Registry\Registration;

/**
 * Class Registry
 *
 * Manages all registered condition sets and conditions.
 */
final class Registry {

	use Registration;

	/**
	 * The post meta key a rule's groups are saved under.
	 *
	 * @var string
	 */
	public const string META_KEY = '_conditions';

	/**
	 * Registered condition sets.
	 *
	 * @var array<string, array>
	 */
	private static array $sets = [];

	/**
	 * Registered conditions by set.
	 *
	 * @var array<string, array<string, array>>
	 */
	private static array $conditions = [];

	/**
	 * PostType instances by set ID.
	 *
	 * @var array<string, PostType>
	 */
	private static array $post_types = [];

	/**
	 * MetaBox instances by set ID.
	 *
	 * @var array<string, MetaBox>
	 */
	private static array $meta_boxes = [];

	/**
	 * REST routes instance.
	 *
	 * @var Routes|null
	 */
	private static ?Routes $routes = null;

	/**
	 * Assets instance.
	 *
	 * @var Assets|null
	 */
	private static ?Assets $assets = null;

	/**
	 * Allowed post types for REST queries.
	 *
	 * @var array<string>
	 */
	private static array $allowed_post_types = [];

	/**
	 * Allowed taxonomies for REST queries.
	 *
	 * @var array<string>
	 */
	private static array $allowed_taxonomies = [];

	/**
	 * Allowed user roles for REST queries.
	 *
	 * @var array<string>
	 */
	private static array $allowed_roles = [];

	/**
	 * Whether the library has been initialized.
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Initialize the library.
	 *
	 * Hooks into WordPress to set up REST endpoints, admin assets, etc.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		// The search endpoints behind the post, term, user and AJAX pickers.
		self::$routes = new Routes();
		self::$routes->register();

		// The editor's scripts and styles, on the sets' edit screens only.
		self::$assets = new Assets();
		self::$assets->register();
	}

	/**
	 * Get a registered condition set.
	 *
	 * @param string $set_id The condition set ID.
	 *
	 * @return array|null Set configuration or null if not found.
	 */
	public static function get_set( string $set_id ): ?array {
		return self::$sets[ $set_id ] ?? null;
	}

	/**
	 * Get all registered condition sets.
	 *
	 * @return array<string, array>
	 */
	public static function get_sets(): array {
		return self::$sets;
	}

	/**
	 * Get a specific condition from a set.
	 *
	 * @param string $set_id       The condition set ID.
	 * @param string $condition_id The condition ID.
	 *
	 * @return array|null Condition configuration or null if not found.
	 */
	public static function get_condition( string $set_id, string $condition_id ): ?array {
		return self::$conditions[ $set_id ][ $condition_id ] ?? null;
	}

	/**
	 * Get all conditions for a set.
	 *
	 * @param string $set_id The condition set ID.
	 *
	 * @return array<string, array>
	 */
	public static function get_conditions( string $set_id ): array {
		$conditions = self::$conditions[ $set_id ] ?? [];

		// Resolve any callable options for JavaScript
		foreach ( $conditions as $key => $condition ) {
			if ( isset( $condition['options'] ) && is_callable( $condition['options'] ) ) {
				$conditions[ $key ]['options'] = call_user_func( $condition['options'] );
			}
			if ( isset( $condition['units'] ) && is_callable( $condition['units'] ) ) {
				$conditions[ $key ]['units'] = call_user_func( $condition['units'] );
			}
		}

		return $conditions;
	}

	/**
	 * Get raw conditions without resolving callables.
	 *
	 * Used internally for AJAX callbacks that need access to closures.
	 *
	 * @param string $set_id The condition set ID.
	 *
	 * @return array<string, array>
	 */
	public static function get_conditions_raw( string $set_id ): array {
		return self::$conditions[ $set_id ] ?? [];
	}

	/**
	 * Check if a post type is allowed for REST queries.
	 *
	 * @param string $post_type The post type to check.
	 *
	 * @return bool
	 */
	public static function is_post_type_allowed( string $post_type ): bool {
		return in_array( $post_type, self::$allowed_post_types, true );
	}

	/**
	 * Check if a taxonomy is allowed for REST queries.
	 *
	 * @param string $taxonomy The taxonomy to check.
	 *
	 * @return bool
	 */
	public static function is_taxonomy_allowed( string $taxonomy ): bool {
		return in_array( $taxonomy, self::$allowed_taxonomies, true );
	}

	/**
	 * The roles the user pickers may search, or none when any may.
	 *
	 * @return string[]
	 */
	public static function allowed_roles(): array {
		return array_values( self::$allowed_roles );
	}

	/**
	 * Check if a role is allowed for REST queries.
	 *
	 * @param string $role The role to check.
	 *
	 * @return bool
	 */
	public static function is_role_allowed( string $role ): bool {
		// If no roles specified, allow all
		if ( empty( self::$allowed_roles ) ) {
			return true;
		}

		return in_array( $role, self::$allowed_roles, true );
	}

	/**
	 * Get the PostType instance for a set.
	 *
	 * @param string $set_id The condition set ID.
	 *
	 * @return PostType|null
	 */
	public static function get_post_type_instance( string $set_id ): ?PostType {
		return self::$post_types[ $set_id ] ?? null;
	}

	/**
	 * Get the MetaBox instance for a set.
	 *
	 * @param string $set_id The condition set ID.
	 *
	 * @return MetaBox|null
	 */
	public static function get_meta_box_instance( string $set_id ): ?MetaBox {
		return self::$meta_boxes[ $set_id ] ?? null;
	}

	/**
	 * Get the REST routes instance.
	 *
	 * @return Routes|null
	 */
	public static function get_routes_instance(): ?Routes {
		return self::$routes;
	}

	/**
	 * Get the Assets instance.
	 *
	 * @return Assets|null
	 */
	public static function get_assets_instance(): ?Assets {
		return self::$assets;
	}

	/**
	 * Check if the library has been initialized.
	 *
	 * @return bool
	 */
	public static function is_initialized(): bool {
		return self::$initialized;
	}

	/**
	 * Reset the registry (primarily for testing).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$sets               = [];
		self::$conditions         = [];
		self::$post_types         = [];
		self::$meta_boxes         = [];
		self::$routes             = null;
		self::$assets             = null;
		self::$allowed_post_types = [];
		self::$allowed_taxonomies = [];
		self::$allowed_roles      = [];
		self::$initialized        = false;
	}
}
