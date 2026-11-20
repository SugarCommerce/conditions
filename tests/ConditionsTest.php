<?php
/**
 * The entry point: registering a set, checking it, and the prefix.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Tests;

use InvalidArgumentException;
use SugarCommerce\Conditions\Admin\Assets;
use SugarCommerce\Conditions\Condition;
use SugarCommerce\Conditions\Conditions;
use SugarCommerce\Conditions\Registry;
use SugarCommerce\Conditions\Rest\Routes;
use PHPUnit\Framework\TestCase;
use WP_Post;
use WP_REST_Request;

/**
 * A class-based condition, as a plugin would write one.
 */
final class CartTotalCondition extends Condition {

	/**
	 * The condition's id.
	 *
	 * @var string
	 */
	protected string $name = 'cart_total';

	/**
	 * The editor's label.
	 *
	 * @var string
	 */
	protected string $label = 'Cart Total';

	/**
	 * The field type.
	 *
	 * @var string
	 */
	protected string $type = 'number';

	/**
	 * The argument it reads.
	 *
	 * @var string|null
	 */
	protected ?string $arg = 'cart_total';

	/**
	 * What it needs to be judged.
	 *
	 * @var array
	 */
	protected array $required_args = [ 'cart_total' ];
}

/**
 * Class ConditionsTest
 */
final class ConditionsTest extends TestCase {

	protected function setUp(): void {
		Conditions::reset();
		wpc_reset();
	}

	protected function tearDown(): void {
		Conditions::reset();
		wpc_reset();
	}

	/**
	 * Store a rule post with groups, for check() and check_all() to read.
	 *
	 * @param int    $id     The post id.
	 * @param string $title  The title.
	 * @param array  $groups The groups.
	 *
	 * @return WP_Post
	 */
	private function rule( int $id, string $title, array $groups ): WP_Post {
		$GLOBALS['wpc_post_meta'][ $id ][ Registry::META_KEY ] = $groups;

		return new WP_Post( $id, $title );
	}

	/**
	 * A group of one rule.
	 *
	 * @param string $condition The condition.
	 * @param string $operator  The operator.
	 * @param mixed  $value     The value.
	 *
	 * @return array
	 */
	private function group( string $condition, string $operator, mixed $value = null ): array {
		return [
			[
				'rules' => [
					[
						'condition' => $condition,
						'operator'  => $operator,
						'value'     => $value,
					],
				],
			],
		];
	}

	/**
	 * Every shared name is built from the prefix.
	 */
	public function test_shared_names_follow_the_prefix(): void {
		$this->assertSame( 'conditions_set_registered', Conditions::hook( 'set_registered' ) );
		$this->assertSame( 'conditions/v1', Conditions::rest_namespace() );
		$this->assertSame( 'conditions-admin', Conditions::handle( 'admin' ) );

		Conditions::use_prefix( 'My Shop_2!' );

		$this->assertSame( 'myshop_2', Conditions::prefix() );
		$this->assertSame( 'myshop_2_conditions_set_registered', Conditions::hook( 'set_registered' ) );
		$this->assertSame( 'myshop-2-conditions/v1', Conditions::rest_namespace() );
		$this->assertSame( 'myshop-2-conditions-admin', Conditions::handle( 'admin' ) );
	}

	/**
	 * A set registers its post type, locked to its capability, its meta,
	 * its meta box and the routes under this copy's namespace.
	 */
	public function test_a_set_registers_everything_it_needs(): void {
		Conditions::use_prefix( 'shop' );

		Conditions::register( 'discount_rule', [
			'labels'     => [ 'singular' => 'Discount Rule' ],
			'capability' => 'manage_shop',
			'conditions' => [ 'user_role', 'is_logged_in' ],
		] );

		[ $post_type, $args ] = $GLOBALS['wpc_calls']['register_post_type'][0];

		$this->assertSame( 'discount_rule', $post_type );
		$this->assertSame( 'Discount Rules', $args['labels']['name'], 'A singular label alone lost the plural.' );
		$this->assertSame( 'manage_shop', $args['capabilities']['delete_published_posts'], 'An Editor could trash a rule.' );
		$this->assertSame( [ 'manage_shop' ], array_values( array_unique( $args['capabilities'] ) ) );
		$this->assertContains( 'page-attributes', $args['supports'], 'Rules are checked in Order, and nothing could set one.' );

		$this->assertSame( [ 'discount_rule', '_conditions' ], array_slice( $GLOBALS['wpc_calls']['register_post_meta'][0], 0, 2 ) );
		$this->assertSame( [ 'shop_conditions_set_registered', 'discount_rule' ], array_slice( $GLOBALS['wpc_calls']['do_action'][0], 0, 2 ) );

		foreach ( $GLOBALS['wpc_filters']['rest_api_init'] as $callback ) {
			$callback();
		}

		$routes = array_map( static fn( array $call ): string => $call[0] . $call[1], $GLOBALS['wpc_calls']['register_rest_route'] );
		$this->assertSame( [ 'shop-conditions/v1/posts', 'shop-conditions/v1/terms', 'shop-conditions/v1/users', 'shop-conditions/v1/ajax' ], $routes );

		$this->assertSame( [ 'user_role', 'is_logged_in' ], array_keys( Registry::get_conditions( 'discount_rule' ) ) );
	}

	/**
	 * A set takes built-in ids, configurations and classes, and add() extends it.
	 */
	public function test_conditions_come_as_ids_configurations_and_classes(): void {
		Conditions::register( 'fee_rule', [
			'conditions' => [
				'day_of_week',
				'cart_country' => [
					'type' => 'text',
					'arg'  => 'country',
				],
				CartTotalCondition::class,
			],
		] );

		Conditions::add( 'fee_rule', 'is_weekend' );
		Conditions::add( 'fee_rule', 'shipping', [ 'type' => 'number' ] );

		$conditions = Registry::get_conditions_raw( 'fee_rule' );

		$this->assertSame( [ 'day_of_week', 'cart_country', 'cart_total', 'is_weekend', 'shipping' ], array_keys( $conditions ) );
		$this->assertSame( 'Cart Country', $conditions['cart_country']['label'] );
		$this->assertSame( 'General', $conditions['shipping']['group'] );
		$this->assertInstanceOf( CartTotalCondition::class, $conditions['cart_total']['instance'] );
	}

	/**
	 * Mistakes are loud at registration rather than silent at checkout.
	 */
	public function test_an_unknown_built_in_is_refused(): void {
		$this->expectException( InvalidArgumentException::class );

		Conditions::register( 'bad_rule', [ 'conditions' => [ 'no_such_condition' ] ] );
	}

	/**
	 * A set cannot be registered twice.
	 */
	public function test_a_set_cannot_be_registered_twice(): void {
		Conditions::register( 'twice' );

		$this->expectException( InvalidArgumentException::class );

		Conditions::register( 'twice' );
	}

	/**
	 * A class that is not a Condition is refused.
	 */
	public function test_a_class_must_be_a_condition(): void {
		$this->expectException( InvalidArgumentException::class );

		Conditions::add( 'some_rule', self::class );
	}

	/**
	 * check() answers with the first matching rule, check_all() with every one.
	 */
	public function test_check_and_check_all(): void {
		Conditions::register( 'discount_rule', [ 'conditions' => [ CartTotalCondition::class ] ] );

		$GLOBALS['wpc_posts'] = [
			$this->rule( 1, 'Big carts', $this->group( 'cart_total', '>=', 100 ) ),
			$this->rule( 2, 'Unfinished', [] ),
			$this->rule( 3, 'Any cart', $this->group( 'cart_total', '>', 0 ) ),
		];

		$first = Conditions::check( 'discount_rule', [ 'cart_total' => 150 ] );
		$this->assertTrue( $first->matched() );
		$this->assertSame( 1, $first->get_rule_id() );
		$this->assertSame( 'cart_total', $first->get_matched_group()['rules'][0]['condition'] );

		$all = Conditions::check_all( 'discount_rule', [ 'cart_total' => 150 ] );
		$this->assertSame( [ 1, 3 ], $all->get_rule_ids() );
		$this->assertSame( [ 'Big carts', 'Any cart' ], $all->get_rule_titles() );

		$small = Conditions::check( 'discount_rule', [ 'cart_total' => 20 ] );
		$this->assertSame( 3, $small->get_rule_id() );

		$none = Conditions::check( 'discount_rule', [] );
		$this->assertFalse( $none->matched(), 'A rule matched without the argument it needs.' );

		$this->assertSame( 'discount_rule', $GLOBALS['wpc_last_query']['post_type'] );
		$this->assertSame( 'publish', $GLOBALS['wpc_last_query']['post_status'] );
	}

	/**
	 * The post type is the set's, whatever the query arguments say.
	 */
	public function test_query_arguments_cannot_change_the_post_type(): void {
		Conditions::check( 'discount_rule', [], [
			'post_type'      => 'page',
			'posts_per_page' => 5,
		] );

		$this->assertSame( 'discount_rule', $GLOBALS['wpc_last_query']['post_type'] );
		$this->assertSame( 5, $GLOBALS['wpc_last_query']['posts_per_page'] );
	}

	/**
	 * Rules kept anywhere are judged with matches().
	 */
	public function test_matches_judges_groups_kept_elsewhere(): void {
		Conditions::register( 'term_rule', [ 'conditions' => [ CartTotalCondition::class ] ] );

		$this->assertTrue( Conditions::matches( 'term_rule', $this->group( 'cart_total', '<', 10 ), [ 'cart_total' => 5 ] ) );
		$this->assertFalse( Conditions::matches( 'term_rule', $this->group( 'cart_total', '<', 10 ), [ 'cart_total' => 50 ] ) );
		$this->assertFalse( Conditions::matches( 'term_rule', [], [ 'cart_total' => 5 ] ) );
	}

	/**
	 * The search endpoints answer to the capability of the set that asks.
	 */
	public function test_the_search_endpoints_answer_to_the_sets_capability(): void {
		Conditions::register( 'fraud_rule', [ 'capability' => 'manage_fraud' ] );
		Conditions::register( 'sale_rule', [ 'capability' => 'manage_sales' ] );

		$routes = new Routes();

		$this->assertFalse( $routes->permission_check( new WP_REST_Request() ), 'Nobody with neither capability may search.' );

		$GLOBALS['wpc_caps'] = [ 'manage_sales' ];

		$this->assertTrue( $routes->permission_check( new WP_REST_Request() ), 'A sales manager could not use the post picker.' );
		$this->assertTrue( $routes->permission_check( new WP_REST_Request( [ 'set_id' => 'sale_rule' ] ) ) );
		$this->assertFalse( $routes->permission_check( new WP_REST_Request( [ 'set_id' => 'fraud_rule' ] ) ), 'Another set\'s capability opened this one.' );
		$this->assertFalse( $routes->permission_check( new WP_REST_Request( [ 'set_id' => 'no_such_set' ] ) ) );

		add_filter( Conditions::hook( 'rest_permission' ), static fn(): bool => false );
		$this->assertFalse( $routes->permission_check( new WP_REST_Request() ), 'The filter did not have the last word.' );
	}

	/**
	 * The editor's data is keyed by set and carries no objects.
	 */
	public function test_the_editor_data_is_keyed_by_set_and_plain(): void {
		Conditions::use_prefix( 'shop' );
		Conditions::register( 'discount_rule', [ 'conditions' => [ CartTotalCondition::class, 'day_of_week' ] ] );

		$GLOBALS['post_type'] = 'discount_rule';
		Registry::get_assets_instance()->enqueue_assets( 'post.php' );
		unset( $GLOBALS['post_type'] );

		$scripts = array_column( $GLOBALS['wpc_calls']['wp_enqueue_script'], 0 );
		$this->assertSame( [ 'shop-conditions-select2', 'shop-conditions-admin' ], $scripts );

		[ $handle, $inline ] = $GLOBALS['wpc_calls']['wp_add_inline_script'][0];
		$this->assertSame( 'shop-conditions-admin', $handle );
		$this->assertStringStartsWith( 'window.sugarConditions = window.sugarConditions || {}; window.sugarConditions["discount_rule"] = {', $inline );

		$data = ( new Assets() )->get_localized_data( 'discount_rule' );
		$this->assertSame( 'https://shop.example/wp-json/shop-conditions/v1', $data['restUrl'] );
		$this->assertArrayNotHasKey( 'instance', $data['conditions']['cart_total'] );
		$this->assertArrayNotHasKey( 'compare_value', $data['conditions']['day_of_week'] );
		$this->assertNotEmpty( $data['conditions']['day_of_week']['options'] );
	}

	/**
	 * The editor loads only on a set's own edit screens.
	 */
	public function test_the_editor_loads_only_on_the_sets_screens(): void {
		Conditions::register( 'discount_rule' );

		$GLOBALS['post_type'] = 'post';
		Registry::get_assets_instance()->enqueue_assets( 'post.php' );

		$GLOBALS['post_type'] = 'discount_rule';
		Registry::get_assets_instance()->enqueue_assets( 'edit.php' );
		unset( $GLOBALS['post_type'] );

		$this->assertArrayNotHasKey( 'wp_enqueue_script', $GLOBALS['wpc_calls'] );
	}

	/**
	 * The assets are served from where this copy of the library sits.
	 */
	public function test_the_assets_url_is_worked_out_from_the_folder(): void {
		$this->assertSame(
			'https://shop.example/wp-content/conditions/assets/build/conditions.js',
			Assets::url( 'build/conditions.js' )
		);

		add_filter( Conditions::hook( 'assets_url' ), static fn(): string => 'https://cdn.example/c/' );
		$this->assertSame( 'https://cdn.example/c/css/conditions.css', Assets::url( 'css/conditions.css' ) );
	}
}
