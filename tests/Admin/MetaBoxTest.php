<?php
/**
 * Saving a rule from the editor.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Tests\Admin;

use SugarCommerce\Conditions\Admin\Editor;
use SugarCommerce\Conditions\Admin\MetaBox;
use SugarCommerce\Conditions\Conditions;
use SugarCommerce\Conditions\Registry;
use PHPUnit\Framework\TestCase;
use WP_Post;

/**
 * Class MetaBoxTest
 */
final class MetaBoxTest extends TestCase {

	protected function setUp(): void {
		Conditions::reset();
		wpc_reset();
		$GLOBALS['wpc_caps'] = [ 'manage_options' ];

		Conditions::register( 'fraud_rule', [ 'conditions' => [ 'user_agent', 'utm_parameter' ] ] );
		Conditions::register( 'sale_rule', [ 'conditions' => [ 'user_agent' ] ] );
	}

	protected function tearDown(): void {
		$_POST = [];
		Conditions::reset();
		wpc_reset();
	}

	/**
	 * Post the editor's form, slashed the way WordPress slashes it.
	 *
	 * @param string $nonce_set The set the nonce was made for.
	 * @param string $post_type The post being saved, and the set saving it.
	 *
	 * @return mixed What was written to the meta.
	 */
	private function save( string $nonce_set = 'fraud_rule', string $post_type = 'fraud_rule' ): mixed {
		$_POST = wp_slash( [
			'conditions_nonce' => wp_create_nonce( Editor::nonce_action( $nonce_set ) ),
			'_conditions'      => [
				'g1' => [
					'rules' => [
						'r1' => [
							'condition' => 'user_agent',
							'operator'  => 'regex',
							'value'     => '/^curl\/\d+$/i',
						],
						'r2' => [
							'condition' => 'utm_parameter',
							'operator'  => '==',
							'value'     => [
								'text' => "O'Brien",
								'unit' => 'campaign',
							],
						],
					],
				],
			],
		] );

		$set = Registry::get_meta_box_instance( $post_type ) ?? Registry::get_meta_box_instance( 'fraud_rule' );
		$set->save_meta( 7, new WP_Post( 7, 'Rule', $post_type ) );

		return $GLOBALS['wpc_post_meta'][7][ Registry::META_KEY ] ?? null;
	}

	/**
	 * A regex and an apostrophe reach the database as they were typed.
	 *
	 * The form is unslashed once; update_post_meta() unslashes what it is
	 * given again, so the value has to be slashed back for it -- or the
	 * pattern lost its backslashes and stopped matching anything.
	 */
	public function test_a_rule_is_saved_as_typed(): void {
		$saved = $this->save();

		$this->assertSame( '/^curl\/\d+$/i', $saved[0]['rules'][0]['value'] );
		$this->assertSame( "O'Brien", $saved[0]['rules'][1]['value']['text'] );
		$this->assertSame( 'campaign', $saved[0]['rules'][1]['value']['unit'] );
	}

	/**
	 * A form for one set cannot write another set's rules.
	 */
	public function test_the_nonce_names_the_set(): void {
		$this->assertNull( $this->save( 'other_rule' ) );
		$this->assertNull( $this->save( 'fraud_rule', 'sale_rule' ), 'A fraud rule form wrote a sale rule.' );
		$this->assertNotNull( $this->save( 'sale_rule', 'sale_rule' ) );
	}

	/**
	 * Only the set's own posts are written.
	 */
	public function test_another_post_type_is_left_alone(): void {
		$this->assertNull( $this->save( 'fraud_rule', 'post' ) );
	}

	/**
	 * Without the set's capability nothing is written.
	 */
	public function test_the_capability_is_checked(): void {
		$GLOBALS['wpc_caps'] = [];

		$this->assertNull( $this->save() );
	}
}
