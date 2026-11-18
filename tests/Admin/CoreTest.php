<?php
/**
 * Where the library hands a job to WordPress core: the trash notice, the
 * REST check, and reading a list from a request.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Tests\Admin;

use PHPUnit\Framework\TestCase;
use SugarCommerce\Conditions\Admin\PostType;
use SugarCommerce\Conditions\Conditions;
use SugarCommerce\Conditions\Helpers\Context;
use SugarCommerce\Conditions\Rest\Ajax;
use WP_REST_Request;

/**
 * Class CoreTest
 */
final class CoreTest extends TestCase {

	/**
	 * A clean slate.
	 */
	protected function setUp(): void {
		Conditions::reset();
		wpc_reset();
		$_GET = [];
	}

	/**
	 * And another for the next test.
	 */
	protected function tearDown(): void {
		Conditions::reset();
		wpc_reset();
		$_GET = [];
	}

	/**
	 * The trash notice is core's success notice, dismissible, with the undo
	 * link inside its paragraph.
	 */
	public function test_the_trash_notice_is_cores(): void {
		$post_type = new PostType(
			'fee_rule',
			[
				'labels' => [
					'singular' => 'Fee Rule',
					'plural'   => 'Fee Rules',
				],
			]
		);

		$_GET = [
			'fee_rule_trashed' => '1',
			'ids'              => '7',
		];

		ob_start();
		$post_type->display_trash_notice();
		$one = (string) ob_get_clean();

		$this->assertSame(
			'<div class="notice notice-success is-dismissible"><p>1 fee rule moved to the Trash. <a href="https://shop.example/wp-admin/post.php?action=untrash&amp;post=7&amp;_wpnonce=nonce">Undo</a></p></div>',
			$one
		);

		$_GET['ids'] = '7,8';

		ob_start();
		$post_type->display_trash_notice();
		$this->assertStringContainsString( '2 fee rules moved to the Trash. <a href="https://shop.example/wp-admin/edit.php?post_status=trash&amp;post_type=fee_rule">', (string) ob_get_clean() );
	}

	/**
	 * Whether this is a REST request is core's answer, unless the arguments
	 * say.
	 */
	public function test_is_rest_asks_core(): void {
		$this->assertFalse( Context::is_rest() );
		$this->assertTrue( Context::is_rest( [ 'is_rest' => true ] ) );
	}

	/**
	 * The ids an ajax picker asks to have labelled are read as core reads a
	 * list: commas or spaces, nothing empty.
	 */
	public function test_the_ajax_endpoint_reads_ids_as_a_list(): void {
		$asked = null;

		Conditions::register(
			'fee_rule',
			[
				'conditions' => [
					'product' => [
						'type' => 'ajax',
						'ajax' => static function ( $search, $ids ) use ( &$asked ): array {
							$asked = $ids;

							return [ 7 => 'Seven' ];
						},
					],
				],
			]
		);

		Ajax::handle(
			new WP_REST_Request(
				[
					'set_id'       => 'fee_rule',
					'condition_id' => 'product',
					'search'       => '',
					'include'      => ' 7, 8 ,,9 ',
				]
			)
		);

		$this->assertSame( [ '7', '8', '9' ], $asked );
	}
}
