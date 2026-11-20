<?php
/**
 * The builder's script: built, registered from its manifest, served from
 * where the assets URL filter says, and the templates it copies.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Tests\Admin;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SugarCommerce\Conditions\Admin\Assets;
use SugarCommerce\Conditions\Admin\Editor;
use SugarCommerce\Conditions\Conditions;
use SugarCommerce\Conditions\Registry;
use SugarCommerce\Plugin\Assets as PackageAssets;
use WP_Post;

/**
 * Class AssetsTest
 */
final class AssetsTest extends TestCase {

	/**
	 * A file in the package, as the library's own classes pass it.
	 */
	private const ASSETS_FILE = __DIR__ . '/../../src/Admin/Assets.php';

	protected function setUp(): void {
		Conditions::reset();
		wpc_reset();
		PackageAssets::clear_cache();
	}

	protected function tearDown(): void {
		Conditions::reset();
		wpc_reset();
		PackageAssets::clear_cache();
		unset( $GLOBALS['post_type'] );
	}

	/**
	 * Enqueue the editor's assets on a set's edit screen.
	 *
	 * @return void
	 */
	private function enqueue(): void {
		Conditions::use_prefix( 'shop' );
		Conditions::register( 'discount_rule' );

		$GLOBALS['post_type'] = 'discount_rule';
		Registry::get_assets_instance()->enqueue_assets( 'post-new.php' );
	}

	/**
	 * The build is committed with its manifest, and imports no jQuery.
	 */
	public function test_every_built_entry_has_its_manifest(): void {
		$manifest = PackageAssets::built_manifest( realpath( self::ASSETS_FILE ), 'conditions' );

		$this->assertNotNull( $manifest );
		$this->assertNotContains( 'jquery', $manifest['dependencies'] );
		$this->assertNotContains( 'wp-util', $manifest['dependencies'] );
		$this->assertSame( [ 'wp-api-fetch', 'wp-dom-ready', 'wp-i18n' ], $manifest['dependencies'] );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{20}$/', (string) $manifest['version'] );

		$this->assertNull( PackageAssets::built_manifest( realpath( self::ASSETS_FILE ), 'nothing' ) );
		// assets/js holds the module the build is made from now, not the old
		// jQuery script that was enqueued as it was.
		$source = (string) file_get_contents( dirname( self::ASSETS_FILE, 3 ) . '/assets/js/conditions.js' );

		$this->assertStringContainsString( "import apiFetch from '@wordpress/api-fetch';", $source );
		$this->assertStringNotContainsString( 'jQuery', $source );
	}

	/**
	 * The builder is registered from its build, after Select2, translated.
	 */
	public function test_the_builder_is_registered_from_its_build(): void {
		$this->enqueue();

		$manifest = PackageAssets::built_manifest( realpath( self::ASSETS_FILE ), 'conditions' );
		$builder  = $GLOBALS['wpc_scripts']['shop-conditions-admin'];

		$this->assertSame( 'https://shop.example/wp-content/conditions/assets/build/conditions.js', $builder->src );
		$this->assertSame( [ ...$manifest['dependencies'], 'shop-conditions-select2' ], $builder->deps );
		$this->assertSame( $manifest['version'], $builder->ver );
		$this->assertSame( [ 'in_footer' => true ], $builder->args );

		$this->assertSame( [ [ 'shop-conditions-admin', 'sugarcommerce' ] ], $GLOBALS['wpc_calls']['wp_set_script_translations'] );

		$enqueued = $GLOBALS['wpc_calls']['wp_enqueue_script'];

		$this->assertSame( [ 'shop-conditions-select2', 'shop-conditions-admin' ], array_column( $enqueued, 0 ) );
		$this->assertSame( 'https://shop.example/wp-content/conditions/assets/js/select2.min.js', $enqueued[0][1] );
		$this->assertSame( [ 'jquery' ], $enqueued[0][2], 'Select2 is a jQuery plugin: it brings jQuery.' );
		$this->assertSame( [ 'shop-conditions-admin' ], $enqueued[1], 'The builder is enqueued as registered.' );

		[ $handle, $inline, $position ] = $GLOBALS['wpc_calls']['wp_add_inline_script'][0];

		$this->assertSame( 'shop-conditions-admin', $handle );
		$this->assertStringStartsWith( 'window.sugarConditions = window.sugarConditions || {}; window.sugarConditions["discount_rule"] = {', $inline );
		$this->assertSame( 'before', $position );
	}

	/**
	 * `{prefix}_conditions_assets_url` moves the builder with the rest.
	 */
	public function test_the_assets_url_filter_moves_the_builder_too(): void {
		add_filter( 'shop_conditions_assets_url', static fn(): string => 'https://cdn.example/c/' );

		$this->enqueue();

		$this->assertSame( 'https://cdn.example/c/build/conditions.js', $GLOBALS['wpc_scripts']['shop-conditions-admin']->src );
		$this->assertSame( 'https://cdn.example/c/js/select2.min.js', $GLOBALS['wpc_calls']['wp_enqueue_script'][0][1] );
	}

	/**
	 * A folder Plugin\Assets cannot find a URL for is served from wherever
	 * the filter says, its manifest read from disk.
	 */
	public function test_a_folder_plugin_assets_cannot_see_is_served_from_the_filter(): void {
		add_filter( 'shop_conditions_assets_url', static fn(): string => 'https://cdn.example/c' );

		// What Plugin\Assets remembers for a package outside every folder
		// WordPress serves: nothing.
		$cache = new ReflectionProperty( PackageAssets::class, 'cache' );
		$file  = dirname( self::ASSETS_FILE ) . '/Assets.php';
		$cache->setValue( null, [ realpath( $file ) => null ] );

		$this->enqueue();

		$manifest = require dirname( self::ASSETS_FILE, 3 ) . '/assets/build/conditions.asset.php';
		$builder  = $GLOBALS['wpc_scripts']['shop-conditions-admin'];

		$this->assertSame( 'https://cdn.example/c/build/conditions.js', $builder->src );
		$this->assertSame( [ ...$manifest['dependencies'], 'shop-conditions-select2' ], $builder->deps );
		$this->assertSame( $manifest['version'], $builder->ver );
		$this->assertTrue( $builder->args );
		$this->assertSame( [ [ 'shop-conditions-admin', 'sugarcommerce' ] ], $GLOBALS['wpc_calls']['wp_set_script_translations'] );
		$this->assertSame( [ 'shop-conditions-select2', 'shop-conditions-admin' ], array_column( $GLOBALS['wpc_calls']['wp_enqueue_script'], 0 ) );
	}

	/**
	 * The editor draws plain templates for the script to copy: no
	 * underscore.js syntax, both group labels, every condition offered.
	 */
	public function test_the_editor_draws_the_templates_the_script_copies(): void {
		Conditions::register(
			'discount_rule',
			[
				'conditions' => [
					'total' => [
						'label' => 'Cart Total',
						'group' => 'Cart',
						'type'  => 'number',
					],
				],
			]
		);

		ob_start();
		Editor::render( new WP_Post( 7, 'Rule', 'discount_rule' ), [ 'args' => [ 'set_id' => 'discount_rule' ] ] );
		$html = (string) ob_get_clean();

		$this->assertStringNotContainsString( '{{', $html );
		$this->assertStringNotContainsString( '<#', $html );
		$this->assertStringNotContainsString( 'type="text/html"', $html );
		$this->assertStringContainsString( 'data-first-label="Match all of the following rules"', $html );
		$this->assertStringContainsString( 'data-other-label="Or match all of the following rules"', $html );

		foreach ( [ 'tmpl-condition-group', 'tmpl-condition-row', 'tmpl-group-connector' ] as $id ) {
			$this->assertMatchesRegularExpression( '/<template id="' . $id . '"/', $html );
		}

		$this->assertStringContainsString( '<optgroup label="Cart"><option value="total">Cart Total</option></optgroup>', $html );
		$this->assertStringContainsString( 'class="condition-groups" data-conditions=\'[]\'', $html );
	}
}
