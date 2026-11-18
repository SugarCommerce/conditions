<?php
/**
 * A user's capabilities and a post's age, as WordPress answers them.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Tests\Helpers;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use SugarCommerce\Conditions\Builtin\User as UserConditions;
use SugarCommerce\Conditions\Helpers\Post;
use SugarCommerce\Conditions\Helpers\User;
use WP_Post;
use WP_User;

final class UserPostTest extends TestCase {

	protected function setUp(): void {
		wpc_reset();

		$GLOBALS['wpc_users'][7] = new WP_User( 7, [ 'read' => true, 'edit_posts' => true, 'upload_files' => false ] );
	}

	/* ─── Capabilities ─────────────────────────────────────────────── */

	/**
	 * A capability a plugin grants through user_has_cap -- a membership, say
	 * -- is not in the user's roles, so allcaps never had it.
	 */
	public function test_a_capability_granted_by_a_filter_counts(): void {
		add_filter(
			'user_has_cap',
			static function ( array $caps ): array {
				$caps['access_members_area'] = true;

				return $caps;
			}
		);

		$this->assertContains( 'access_members_area', User::get_capabilities( [ 'user_id' => 7 ], [ 'access_members_area' ] ) );
	}

	/**
	 * And one a filter takes away no longer counts.
	 */
	public function test_a_capability_removed_by_a_filter_does_not_count(): void {
		add_filter(
			'user_has_cap',
			static function ( array $caps ): array {
				$caps['edit_posts'] = false;

				return $caps;
			}
		);

		$this->assertNotContains( 'edit_posts', User::get_capabilities( [ 'user_id' => 7 ], [ 'edit_posts' ] ) );
	}

	/**
	 * What the roles grant is still there, asked about or not.
	 */
	public function test_the_roles_own_capabilities_are_kept(): void {
		$held = User::get_capabilities( [ 'user_id' => 7 ], [ 'read' ] );

		$this->assertContains( 'read', $held );
		$this->assertContains( 'edit_posts', $held );
		$this->assertNotContains( 'upload_files', $held );
		$this->assertSame( [ 'read', 'edit_posts' ], User::get_capabilities( [ 'user_id' => 7 ] ) );
	}

	/**
	 * Nobody signed in has nothing.
	 */
	public function test_no_user_has_nothing(): void {
		$this->assertSame( [], User::get_capabilities( [ 'user_id' => 99 ], [ 'read' ] ) );
	}

	/**
	 * The built-in condition hands the rule's capabilities to the helper.
	 */
	public function test_the_has_capability_condition_asks_user_can(): void {
		add_filter(
			'user_has_cap',
			static function ( array $caps ): array {
				$caps['access_members_area'] = true;

				return $caps;
			}
		);

		$conditions = UserConditions::get_all();
		$compare    = $conditions['has_capability']['compare_value'];

		$this->assertContains( 'access_members_area', $compare( [ 'user_id' => 7 ], [ 'access_members_area' ] ) );
	}

	/* ─── Post age ─────────────────────────────────────────────────── */

	/**
	 * A post on a site at UTC+10.
	 *
	 * @param string      $local Its local date.
	 * @param string|null $gmt   Its gmt date; null for a draft's zeros.
	 *
	 * @return WP_Post
	 */
	private static function post( string $local, ?string $gmt ): WP_Post {
		$GLOBALS['wpc_timezone'] = 'Australia/Brisbane';

		$post                    = new WP_Post( 5, 'A draft' );
		$post->post_date         = $local;
		$post->post_modified     = $local;
		$post->post_date_gmt     = $gmt ?? '0000-00-00 00:00:00';
		$post->post_modified_gmt = $gmt ?? '0000-00-00 00:00:00';

		$GLOBALS['wpc_post_by_id'][5] = $post;

		return $post;
	}

	/**
	 * A draft has no gmt date, and its local one is in the site's timezone:
	 * read as UTC, a draft saved a minute ago on a UTC+10 site was ten hours
	 * old (or, west of Greenwich, not made yet).
	 */
	public function test_a_drafts_age_is_read_in_the_sites_timezone(): void {
		$minute_ago = ( new \DateTimeImmutable( '-1 minute', new \DateTimeZone( 'Australia/Brisbane' ) ) )->format( 'Y-m-d H:i:s' );

		self::post( $minute_ago, null );

		$this->assertSame( 0, Post::get_age( [ 'post_id' => 5, '_unit' => 'hour' ] ) );
		$this->assertSame( 1, Post::get_age( [ 'post_id' => 5, '_unit' => 'minute' ] ) );
		$this->assertSame( 0, Post::get_modified_age( [ 'post_id' => 5, '_unit' => 'hour' ] ) );
	}

	/**
	 * A published post: the same moment however it is read.
	 */
	public function test_a_published_posts_age_is_unchanged(): void {
		$zone = new \DateTimeZone( 'Australia/Brisbane' );
		$then = new \DateTimeImmutable( '-3 days -1 minute', $zone );

		self::post( $then->format( 'Y-m-d H:i:s' ), $then->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) );

		$this->assertSame( 3, Post::get_age( [ 'post_id' => 5, '_unit' => 'day' ] ) );
		$this->assertSame( 3, Post::get_modified_age( [ 'post_id' => 5, '_unit' => 'day' ] ) );
	}

	/**
	 * Modified is its own column.
	 */
	public function test_the_modified_age_reads_the_modified_date(): void {
		$zone = new \DateTimeZone( 'Australia/Brisbane' );
		$post = self::post( ( new \DateTimeImmutable( '-10 days', $zone ) )->format( 'Y-m-d H:i:s' ), null );

		$post->post_modified = ( new \DateTimeImmutable( '-2 days -1 minute', $zone ) )->format( 'Y-m-d H:i:s' );

		$this->assertSame( 10, Post::get_age( [ 'post_id' => 5, '_unit' => 'day' ] ) );
		$this->assertSame( 2, Post::get_modified_age( [ 'post_id' => 5, '_unit' => 'day' ] ) );
	}

	/**
	 * A post with no date at all has no age, not two thousand years.
	 */
	public function test_a_post_with_no_date_has_no_age(): void {
		self::post( '0000-00-00 00:00:00', null );

		$this->assertSame( 0, Post::get_age( [ 'post_id' => 5, '_unit' => 'day' ] ) );
	}

	/**
	 * In WordPress the answer is core's own get_post_timestamp().
	 */
	#[RunInSeparateProcess]
	public function test_wordpress_answers_the_age_when_it_can(): void {
		require_once __DIR__ . '/../fixtures/get-post-timestamp.php';

		self::post( '2001-01-01 00:00:00', null );

		$this->assertSame( 2, Post::get_age( [ 'post_id' => 5, '_unit' => 'hour' ] ) );
		$this->assertSame( [ 'date' ], array_column( $GLOBALS['wpc_calls']['get_post_timestamp'] ?? [], 1 ) );
	}
}
