<?php
/**
 * Admin Assets
 *
 * Handles script and style enqueuing for the conditions admin interface.
 *
 * @package     SugarCommerce\Conditions\Admin
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Admin;

use SugarCommerce\Conditions\Conditions;
use SugarCommerce\Conditions\Operators;
use SugarCommerce\Conditions\Registry;
use SugarCommerce\Plugin\Assets as PackageAssets;

/**
 * Class Assets
 *
 * Manages admin assets for conditions UI.
 */
final class Assets {

	/**
	 * The bundled Select2's version.
	 *
	 * @var string
	 */
	private const string SELECT2_VERSION = '4.1.0';

	/**
	 * Registered condition set IDs.
	 *
	 * @var array
	 */
	private array $set_ids = [];

	/**
	 * Constructor.
	 *
	 * @param array $set_ids Array of condition set IDs.
	 */
	public function __construct( array $set_ids = [] ) {
		$this->set_ids = $set_ids;
	}

	/**
	 * Register asset hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Add a set ID to track.
	 *
	 * @param string $set_id The condition set ID.
	 *
	 * @return void
	 */
	public function add_set_id( string $set_id ): void {
		if ( ! in_array( $set_id, $this->set_ids, true ) ) {
			$this->set_ids[] = $set_id;
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		global $post_type;

		// Only load on our CPT edit screens
		if ( ! is_string( $post_type ) || ! in_array( $post_type, $this->set_ids, true ) ) {
			return;
		}

		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$select2 = Conditions::handle( 'select2' );
		$admin   = Conditions::handle( 'admin' );

		wp_enqueue_style( $select2, self::url( 'css/select2.min.css' ), [], self::SELECT2_VERSION );
		wp_enqueue_script( $select2, self::url( 'js/select2.min.js' ), [ 'jquery' ], self::SELECT2_VERSION, true );

		wp_enqueue_style( $admin, self::url( 'css/conditions.css' ), [ $select2 ], self::version( 'css/conditions.css' ) );

		// The builder, from its build: its manifest names what it imports
		// (wp-api-fetch for the searched fields, wp-dom-ready, wp-i18n). Select2
		// is the one thing it needs without importing, and it brings jQuery.
		if ( ! self::register_builder( $admin, [ $select2 ] ) ) {
			return;
		}

		wp_enqueue_script( $admin );

		// Keyed by set rather than one global, so whichever plugin's copy of
		// this library drew the screen, the script reads that copy's data.
		wp_add_inline_script(
			$admin,
			sprintf(
				'window.sugarConditions = window.sugarConditions || {}; window.sugarConditions[%s] = %s;',
				wp_json_encode( $post_type ),
				wp_json_encode( $this->get_localized_data( $post_type ) )
			),
			'before'
		);
	}

	/**
	 * Register the builder's script, `assets/build/conditions.js`.
	 *
	 * Plugin\Assets registers it from its manifest. `{prefix}_conditions_assets_url`
	 * still has the last word on where it is served from, as it has for the
	 * stylesheets and Select2: a folder moved to a CDN takes the script with
	 * it, and a folder Plugin\Assets cannot see at all is served from wherever
	 * the filter says, with the manifest read from disk.
	 *
	 * @param string   $handle The handle.
	 * @param string[] $deps   Handles it needs that it does not import.
	 *
	 * @return bool False when the script was never built.
	 */
	private static function register_builder( string $handle, array $deps ): bool {
		$file = 'build/conditions.js';
		$url  = self::url( $file );

		if ( PackageAssets::register_built_script( $handle, __FILE__, 'conditions', $deps ) ) {
			if ( $url !== PackageAssets::url( __FILE__, $file ) ) {
				wp_scripts()->registered[ $handle ]->src = $url;
			}

			return true;
		}

		$manifest = self::dir() . '/build/conditions.asset.php';

		if ( ! is_readable( $manifest ) ) {
			return false;
		}

		$asset        = require $manifest;
		$dependencies = array_map( 'strval', (array) ( $asset['dependencies'] ?? [] ) );

		wp_register_script( $handle, $url, array_merge( $dependencies, $deps ), (string) ( $asset['version'] ?? '' ), true );

		if ( in_array( 'wp-i18n', $dependencies, true ) ) {
			wp_set_script_translations( $handle, 'sugarcommerce' );
		}

		return true;
	}

	/**
	 * Get localized data for JavaScript.
	 *
	 * @param string $post_type The current post type.
	 *
	 * @return array
	 */
	public function get_localized_data( string $post_type ): array {
		$data = [
			'conditions' => self::for_script( Registry::get_conditions( $post_type ) ),
			'operators'  => Operators::get_all(),
			'restUrl'    => rest_url( Conditions::rest_namespace() ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'i18n'       => $this->get_i18n_strings(),
		];

		/**
		 * Filter the data handed to the builder script:
		 * `{prefix}_conditions_localized_data`.
		 *
		 * @param array  $data      The localized data.
		 * @param string $post_type The current post type.
		 */
		return apply_filters( Conditions::hook( 'localized_data' ), $data, $post_type );
	}

	/**
	 * Conditions as the script needs them: no closures, no objects.
	 *
	 * A class-based condition carries its own instance and a configured one
	 * its callbacks; JSON writes either as `{}`, and a condition object that
	 * held a closure could not be encoded at all.
	 *
	 * @param array $conditions Resolved conditions.
	 *
	 * @return array
	 */
	private static function for_script( array $conditions ): array {
		foreach ( $conditions as $id => $condition ) {
			foreach ( $condition as $key => $value ) {
				if ( is_object( $value ) ) {
					unset( $conditions[ $id ][ $key ] );
				}
			}
		}

		return $conditions;
	}

	/**
	 * Get i18n strings for JavaScript.
	 *
	 * @return array
	 */
	private function get_i18n_strings(): array {
		$strings = [
			'selectCondition' => __( 'Select condition...', 'sugarcommerce' ),
			'selectOperator'  => __( 'Select operator...', 'sugarcommerce' ),
			'selectValue'     => __( 'Select...', 'sugarcommerce' ),
			'search'          => __( 'Search...', 'sugarcommerce' ),
			'addCondition'    => __( 'Add Condition', 'sugarcommerce' ),
			'addGroup'        => __( 'Add "OR" Group', 'sugarcommerce' ),
			'remove'          => __( 'Remove', 'sugarcommerce' ),
			'and'             => __( 'AND', 'sugarcommerce' ),
			'or'              => __( 'OR', 'sugarcommerce' ),
			'duplicate'       => __( 'Duplicate', 'sugarcommerce' ),
			'delete'          => __( 'Delete', 'sugarcommerce' ),
			'matchAll'        => __( 'Match all of the following rules', 'sugarcommerce' ),
			'orMatchAll'      => __( 'Or match all of the following rules', 'sugarcommerce' ),
			'noResults'       => __( 'No results found', 'sugarcommerce' ),
			'loading'         => __( 'Loading...', 'sugarcommerce' ),
			'errorLoading'    => __( 'Error loading results', 'sugarcommerce' ),
		];

		/**
		 * Filter the builder's strings: `{prefix}_conditions_i18n_strings`.
		 *
		 * @param array $strings The i18n strings.
		 */
		return apply_filters( Conditions::hook( 'i18n_strings' ), $strings );
	}

	/** Files *********************************************************************/

	/*
	 * Where the files are is SugarCommerce\Plugin\Assets' to work out, from
	 * where this copy of the library sits -- under the content directory, the
	 * site root, or a symlinked plugin. What stays here is what it does not
	 * do: the `{prefix}_conditions_assets_url` override, and versions from
	 * when each file last changed.
	 */

	/**
	 * The library's assets folder on disk.
	 *
	 * @return string
	 */
	public static function dir(): string {
		return dirname( __DIR__, 2 ) . '/assets';
	}

	/**
	 * The URL of a file in the assets folder.
	 *
	 * `{prefix}_conditions_assets_url` overrides the folder's URL for a setup
	 * Plugin\Assets cannot see, such as a CDN.
	 *
	 * @param string $file e.g. `build/conditions.js`.
	 *
	 * @return string
	 */
	public static function url( string $file ): string {
		$base = (string) apply_filters( Conditions::hook( 'assets_url' ), self::base_url() );

		return rtrim( $base, '/' ) . '/' . ltrim( $file, '/' );
	}

	/**
	 * A file's version: when it last changed, so a new copy is fetched.
	 *
	 * @param string $file e.g. `css/conditions.css`.
	 *
	 * @return string
	 */
	private static function version( string $file ): string {
		$time = @filemtime( self::dir() . '/' . $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a missing file is a version, not a warning.

		return false === $time ? '1.0.0' : (string) $time;
	}

	/**
	 * The assets folder's URL.
	 *
	 * @return string Empty when the folder is somewhere WordPress does not serve.
	 */
	public static function base_url(): string {
		return PackageAssets::locate( __FILE__ )['url'] ?? '';
	}
}
