<?php
/**
 * Post Helper
 *
 * Provides utilities for retrieving post data from condition arguments.
 *
 * @package     SugarCommerce\Conditions\Helpers\Data
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Helpers;

use WP_Post;

/**
 * Class Post
 *
 * Utilities for retrieving post data in conditions.
 */
final class Post {

	/**
	 * Get post object from args.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return WP_Post|null
	 */
	public static function get( array $args ): ?WP_Post {
		$post_id = $args['post_id'] ?? get_the_ID();

		if ( ! $post_id ) {
			return null;
		}

		return get_post( $post_id );
	}

	/**
	 * Get the post status.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string The post status, or empty string if not found.
	 */
	public static function get_status( array $args ): string {
		$post = self::get( $args );

		return $post?->post_status ?? '';
	}

	/**
	 * Get the post type.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string The post type, or empty string if not found.
	 */
	public static function get_type( array $args ): string {
		$post = self::get( $args );

		return $post?->post_type ?? '';
	}

	/**
	 * Get the post author ID.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return int The author ID, or 0 if not found.
	 */
	public static function get_author( array $args ): int {
		$post = self::get( $args );

		return (int) ( $post?->post_author ?? 0 );
	}

	/**
	 * Get post terms for a taxonomy.
	 *
	 * @param array  $args     The arguments including post_id.
	 * @param string $taxonomy The taxonomy to get terms from.
	 *
	 * @return array<int>
	 */
	public static function get_terms( array $args, string $taxonomy ): array {
		$post_id = $args['post_id'] ?? get_the_ID();

		if ( ! $post_id ) {
			return [];
		}

		$terms = wp_get_post_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		return $terms;
	}

	/**
	 * Get all term IDs from all taxonomies for a post.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return array<int>
	 */
	public static function get_all_terms( array $args ): array {
		$post_id = $args['post_id'] ?? get_the_ID();

		if ( ! $post_id ) {
			return [];
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return [];
		}

		$taxonomies = get_object_taxonomies( $post->post_type );
		$all_terms  = [];

		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_post_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );

			if ( ! is_wp_error( $terms ) ) {
				$all_terms = array_merge( $all_terms, $terms );
			}
		}

		return array_unique( $all_terms );
	}

	/**
	 * Get post age in the specified unit.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return int The age in the specified unit, or 0 if post not found.
	 */
	public static function get_age( array $args ): int {
		$post = self::get( $args );

		if ( ! $post ) {
			return 0;
		}

		$parsed = Parse::number_unit( $args );

		return self::age( $post, 'date', $parsed['unit'] );
	}

	/**
	 * Get the post title.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string
	 */
	public static function get_title( array $args ): string {
		$post = self::get( $args );

		return $post ? (string) $post->post_title : '';
	}

	/**
	 * Get the post slug.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string
	 */
	public static function get_slug( array $args ): string {
		$post = self::get( $args );

		return $post ? (string) $post->post_name : '';
	}

	/**
	 * Whether the post is password protected.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return bool
	 */
	public static function is_password_protected( array $args ): bool {
		$post = self::get( $args );

		return $post ? '' !== (string) $post->post_password : false;
	}

	/**
	 * Get the post's menu order.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return int
	 */
	public static function get_menu_order( array $args ): int {
		$post = self::get( $args );

		return $post ? (int) $post->menu_order : 0;
	}

	/**
	 * Get the parent post ID.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return int The parent post ID, or 0 if no parent.
	 */
	public static function get_parent( array $args ): int {
		$post = self::get( $args );

		return (int) ( $post?->post_parent ?? 0 );
	}

	/**
	 * Get the page template slug.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string The template slug, or empty string if not found.
	 */
	public static function get_template( array $args ): string {
		$post = self::get( $args );

		if ( ! $post ) {
			return '';
		}

		// Core stores no template as '', and the option list calls that
		// 'default', so the two have to meet somewhere.
		return get_page_template_slug( $post->ID ) ?: 'default';
	}

	/**
	 * Get the post format.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string The post format, or 'standard' if none set.
	 */
	public static function get_format( array $args ): string {
		$post = self::get( $args );

		if ( ! $post ) {
			return '';
		}

		$format = get_post_format( $post->ID );

		return $format ?: 'standard';
	}

	/**
	 * Check if post has a featured image.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return bool True if post has featured image.
	 */
	public static function has_featured_image( array $args ): bool {
		$post = self::get( $args );

		return $post && has_post_thumbnail( $post->ID );
	}

	/**
	 * Get the comment count for a post.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return int The comment count.
	 */
	public static function get_comment_count( array $args ): int {
		$post = self::get( $args );

		return (int) ( $post?->comment_count ?? 0 );
	}

	/**
	 * Get the comment status for a post.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string The comment status ('open' or 'closed').
	 */
	public static function get_comment_status( array $args ): string {
		$post = self::get( $args );

		return $post?->comment_status ?? '';
	}

	/**
	 * Check if post is sticky.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return bool True if post is sticky.
	 */
	public static function is_sticky( array $args ): bool {
		$post = self::get( $args );

		return $post && is_sticky( $post->ID );
	}

	/**
	 * Check if post has a manual excerpt.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return bool True if post has an excerpt.
	 */
	public static function has_excerpt( array $args ): bool {
		$post = self::get( $args );

		return $post && has_excerpt( $post->ID );
	}

	/**
	 * Get the word count of post content.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return int The word count.
	 */
	public static function get_word_count( array $args ): int {
		$post = self::get( $args );

		if ( ! $post ) {
			return 0;
		}

		$content = wp_strip_all_tags( $post->post_content );

		return str_word_count( $content );
	}

	/**
	 * Check if post contains a specific shortcode.
	 *
	 * @param array       $args       The condition arguments.
	 * @param string|null $user_value The shortcode tag to check for.
	 *
	 * @return bool True if post contains the shortcode.
	 */
	public static function has_shortcode( array $args, ?string $user_value ): bool {
		$post = self::get( $args );

		if ( ! $post || empty( $user_value ) ) {
			return false;
		}

		return has_shortcode( $post->post_content, $user_value );
	}

	/**
	 * Check if post contains a specific Gutenberg block.
	 *
	 * @param array       $args       The condition arguments.
	 * @param string|null $user_value The block name to check for (e.g., 'core/image').
	 *
	 * @return bool True if post contains the block.
	 */
	public static function has_block( array $args, ?string $user_value ): bool {
		$post = self::get( $args );

		if ( ! $post || empty( $user_value ) ) {
			return false;
		}

		return has_block( $user_value, $post );
	}

	/**
	 * Get post meta value as text.
	 *
	 * @param array       $args       The condition arguments.
	 * @param string|null $user_value The user value in format "meta_key:value".
	 *
	 * @return string The meta value or empty string if not found.
	 */
	public static function get_meta_text( array $args, ?string $user_value ): string {
		$post = self::get( $args );

		if ( ! $post ) {
			return '';
		}

		$parsed = Parse::meta( $user_value ?? '' );

		return (string) get_post_meta( $post->ID, $parsed['key'], true );
	}

	/**
	 * Get post meta value as number.
	 *
	 * @param array       $args       The condition arguments.
	 * @param string|null $user_value The user value in format "meta_key:value".
	 *
	 * @return float The meta value or 0 if not found.
	 */
	public static function get_meta_number( array $args, ?string $user_value ): float {
		$post = self::get( $args );

		if ( ! $post ) {
			return 0;
		}

		$parsed = Parse::meta_typed( $user_value ?? '', 'number' );

		return (float) get_post_meta( $post->ID, $parsed['key'], true );
	}

	/**
	 * Get post creation date.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string Date in Y-m-d format.
	 */
	public static function get_date_created( array $args ): string {
		$post = self::get( $args );

		if ( ! $post ) {
			return '';
		}

		return get_the_date( 'Y-m-d', $post );
	}

	/**
	 * Get post modified date.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string Date in Y-m-d format.
	 */
	public static function get_date_modified( array $args ): string {
		$post = self::get( $args );

		if ( ! $post ) {
			return '';
		}

		return get_the_modified_date( 'Y-m-d', $post );
	}

	/**
	 * Get time since post was modified.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return int Age in the specified unit.
	 */
	public static function get_modified_age( array $args ): int {
		$post = self::get( $args );

		if ( ! $post ) {
			return 0;
		}

		$parsed = Parse::number_unit( $args );

		return self::age( $post, 'modified', $parsed['unit'] );
	}

	/**
	 * How long ago a post was published or modified.
	 *
	 * `get_post_timestamp()` reads the local column in the site's timezone,
	 * which is right for a draft too. A draft has no gmt date yet (all
	 * zeros), and its local date read as UTC was off by the site's offset:
	 * on a UTC+10 site a draft saved a minute ago was ten hours old.
	 *
	 * @param WP_Post $post  The post.
	 * @param string  $field 'date' or 'modified'.
	 * @param string  $unit  The unit to return.
	 *
	 * @return int
	 */
	private static function age( WP_Post $post, string $field, string $unit ): int {
		$timestamp = function_exists( 'get_post_timestamp' )
			? get_post_timestamp( $post, $field )
			: self::local_timestamp( (string) ( 'modified' === $field ? $post->post_modified : $post->post_date ) );

		return is_int( $timestamp ) ? DateTime::get_age_from_timestamp( $timestamp, $unit ) : 0;
	}

	/**
	 * A post's local date column as a timestamp, read in the site's timezone.
	 *
	 * What `get_post_timestamp()` does, for when it is not loaded.
	 *
	 * @param string $local The local column.
	 *
	 * @return int|false
	 */
	private static function local_timestamp( string $local ): int|false {
		if ( '' === $local || str_starts_with( $local, '0000' ) ) {
			return false;
		}

		$zone = function_exists( 'wp_timezone' ) ? wp_timezone() : new \DateTimeZone( 'UTC' );
		$date = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $local, $zone );

		return false === $date ? false : $date->getTimestamp();
	}
}
