<?php
/**
 * Security tests.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Tests;

use PHPUnit\Framework\TestCase;
use SugarCommerce\Conditions\Registry;
use SugarCommerce\Conditions\Rest\Users;
use WP_REST_Request;

/**
 * What an attacker would try against the rule builder's search routes.
 */
final class SecurityTest extends TestCase {

	/**
	 * A set whose user picker offers customers only.
	 */
	protected function setUp(): void {
		wpc_reset();
		Registry::reset();
		unset( $GLOBALS['wpc_user_query'] );

		Registry::register_set(
			'offers',
			[
				'capability' => 'edit_offers',
				'conditions' => [
					'customer' => [
						'type' => 'user',
						'role' => [ 'customer' ],
					],
				],
			]
		);
	}

	/**
	 * Forget the set.
	 */
	protected function tearDown(): void {
		Registry::reset();
	}

	/**
	 * A picker limited to some roles cannot be asked about the rest by
	 * leaving the role out.
	 *
	 * The role was checked only when one was sent; without one the route
	 * searched every account, administrators included.
	 */
	public function test_leaving_the_role_out_does_not_widen_the_search(): void {
		$GLOBALS['wpc_caps'] = [ 'edit_offers' ];

		Users::search( new WP_REST_Request( [ 'search' => 'admin' ] ) );

		$this->assertSame( [ 'customer' ], $GLOBALS['wpc_user_query']['role__in'] ?? null );
	}

	/**
	 * Email addresses are searched only for someone who may list users.
	 *
	 * As core's own users route: anyone else searching `@example.com` would
	 * learn whose addresses are on the site.
	 */
	public function test_only_someone_who_may_list_users_searches_their_email(): void {
		$GLOBALS['wpc_caps'] = [ 'edit_offers' ];

		Users::search( new WP_REST_Request( [ 'search' => '@example.com', 'role' => 'customer' ] ) );

		$this->assertNotContains( 'user_email', $GLOBALS['wpc_user_query']['search_columns'] );

		$GLOBALS['wpc_caps'] = [ 'edit_offers', 'list_users' ];

		Users::search( new WP_REST_Request( [ 'search' => '@example.com', 'role' => 'customer' ] ) );

		$this->assertContains( 'user_email', $GLOBALS['wpc_user_query']['search_columns'] );
	}
}
