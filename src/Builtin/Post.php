<?php
/**
 * Post Built-in Conditions
 *
 * @package     SugarCommerce\Conditions\Builtin
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Builtin;

use SugarCommerce\Conditions\Helpers\Parse;
use SugarCommerce\Conditions\Helpers\Post as PostHelper;
use SugarCommerce\Conditions\Options\WordPress;
use SugarCommerce\Conditions\Options\Periods;
use SugarCommerce\Conditions\Operators;

/**
 * Class Post
 *
 * Provides post related conditions.
 */
final class Post {

	/**
	 * Get all post conditions.
	 *
	 * @return array<string, array>
	 */
	public static function get_all(): array {
		return array_merge(
			self::get_detail_conditions(),
			self::get_date_conditions(),
			self::get_taxonomy_conditions(),
			self::get_content_conditions(),
			self::get_meta_conditions()
		);
	}

	/**
	 * Get detail-related conditions.
	 *
	 * @return array<string, array>
	 */
	private static function get_detail_conditions(): array {
		return [
			'post_title'    => [
				'label'         => __( 'Title', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'text',
				'placeholder'   => __( 'e.g. Hello World', 'sugarcommerce' ),
				'description'   => __( 'Match against the post title.', 'sugarcommerce' ),
				'operators'     => Operators::text_advanced(),
				'compare_value' => fn( $args ) => PostHelper::get_title( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_slug'     => [
				'label'         => __( 'Slug', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'text',
				'placeholder'   => __( 'e.g. hello-world', 'sugarcommerce' ),
				'description'   => __( 'Match against the post slug.', 'sugarcommerce' ),
				'operators'     => Operators::text_advanced(),
				'compare_value' => fn( $args ) => PostHelper::get_slug( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_status'   => [
				'label'         => __( 'Status', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select statuses...', 'sugarcommerce' ),
				'description'   => __( 'Match against the post status.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => WordPress::get_post_statuses(),
				'compare_value' => fn( $args ) => PostHelper::get_status( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_type'     => [
				'label'         => __( 'Type', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select post types...', 'sugarcommerce' ),
				'description'   => __( 'Match against the post type.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => WordPress::get_post_types(),
				'compare_value' => fn( $args ) => PostHelper::get_type( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_author'   => [
				'label'         => __( 'Author', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'user',
				'multiple'      => true,
				'placeholder'   => __( 'Search authors...', 'sugarcommerce' ),
				'description'   => __( 'Match against the post author.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => PostHelper::get_author( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_parent'   => [
				'label'         => __( 'Parent', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'post',
				'post_type'     => 'page',
				'multiple'      => true,
				'placeholder'   => __( 'Search parent pages...', 'sugarcommerce' ),
				'description'   => __( 'Match against the parent post/page.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'compare_value' => fn( $args ) => PostHelper::get_parent( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_template' => [
				'label'         => __( 'Page Template', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select template...', 'sugarcommerce' ),
				'description'   => __( 'Match against the page template.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => WordPress::get_page_templates(),
				'compare_value' => fn( $args ) => PostHelper::get_template( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_format'   => [
				'label'         => __( 'Post Format', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select format...', 'sugarcommerce' ),
				'description'   => __( 'Match against the post format.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => WordPress::get_post_formats(),
				'compare_value' => fn( $args ) => PostHelper::get_format( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_password_protected' => [
				'label'         => __( 'Password Protected', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'boolean',
				'description'   => __( 'Whether the post is behind a password.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => PostHelper::is_password_protected( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_menu_order' => [
				'label'         => __( 'Menu Order', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'number',
				'placeholder'   => __( 'e.g. 0', 'sugarcommerce' ),
				'step'          => 1,
				'description'   => __( 'The post\'s menu order. Pages and ordered post types use it for sorting; everything else leaves it at 0.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => PostHelper::get_menu_order( $args ),
				'required_args' => [ 'post_id' ],
			],
		];
	}

	/**
	 * Get date-related conditions.
	 *
	 * @return array<string, array>
	 */
	private static function get_date_conditions(): array {
		return [
			'post_date_created'  => [
				'label'         => __( 'Date Created', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'date',
				'description'   => __( 'Match against the post creation date.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => PostHelper::get_date_created( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_date_modified' => [
				'label'         => __( 'Date Modified', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'date',
				'description'   => __( 'Match against the post last modified date.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => PostHelper::get_date_modified( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_age'           => [
				'label'         => __( 'Age', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'number_unit',
				'placeholder'   => __( 'e.g. 30', 'sugarcommerce' ),
				'description'   => __( 'How long since the post was published.', 'sugarcommerce' ),
				'min'           => 0,
				'units'         => Periods::get_age_units(),
				'compare_value' => fn( $args ) => PostHelper::get_age( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_modified_age'  => [
				'label'         => __( 'Time Since Modified', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'number_unit',
				'placeholder'   => __( 'e.g. 30', 'sugarcommerce' ),
				'description'   => __( 'How long since the post was last modified.', 'sugarcommerce' ),
				'min'           => 0,
				'units'         => Periods::get_age_units(),
				'compare_value' => fn( $args ) => PostHelper::get_modified_age( $args ),
				'required_args' => [ 'post_id' ],
			],
		];
	}

	/**
	 * Get taxonomy-related conditions.
	 *
	 * @return array<string, array>
	 */
	private static function get_taxonomy_conditions(): array {
		return [
			'post_category' => [
				'label'         => __( 'Category', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'term',
				'taxonomy'      => 'category',
				'multiple'      => true,
				'placeholder'   => __( 'Search categories...', 'sugarcommerce' ),
				'description'   => __( 'Match against post categories.', 'sugarcommerce' ),
				'operators'     => Operators::collection(),
				'compare_value' => fn( $args ) => PostHelper::get_terms( $args, 'category' ),
				'required_args' => [ 'post_id' ],
			],
			'post_tag'      => [
				'label'         => __( 'Tag', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'term',
				'taxonomy'      => 'post_tag',
				'multiple'      => true,
				'placeholder'   => __( 'Search tags...', 'sugarcommerce' ),
				'description'   => __( 'Match against post tags.', 'sugarcommerce' ),
				'operators'     => Operators::collection(),
				'compare_value' => fn( $args ) => PostHelper::get_terms( $args, 'post_tag' ),
				'required_args' => [ 'post_id' ],
			],
			'has_term'      => [
				'label'         => __( 'Has Term', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'term',
				'taxonomy'      => 'category',
				'multiple'      => true,
				'placeholder'   => __( 'Search terms...', 'sugarcommerce' ),
				'description'   => __( 'Check if post has specific terms (specify taxonomy when registering).', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => PostHelper::get_all_terms( $args ),
				'required_args' => [ 'post_id' ],
			],
		];
	}

	/**
	 * Get content-related conditions.
	 *
	 * @return array<string, array>
	 */
	private static function get_content_conditions(): array {
		return [
			'has_featured_image'  => [
				'label'         => __( 'Featured Image', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'boolean',
				'description'   => __( 'Check if the post has a featured image.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => PostHelper::has_featured_image( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_comment_count'  => [
				'label'         => __( 'Comment Count', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'number',
				'placeholder'   => __( 'e.g. 10', 'sugarcommerce' ),
				'min'           => 0,
				'step'          => 1,
				'description'   => __( 'The number of comments on the post.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => PostHelper::get_comment_count( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_comment_status' => [
				'label'         => __( 'Comment Status', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'select',
				'multiple'      => false,
				'placeholder'   => __( 'Select status...', 'sugarcommerce' ),
				'description'   => __( 'Whether comments are open or closed.', 'sugarcommerce' ),
				'options'       => WordPress::get_comment_statuses(),
				'compare_value' => fn( $args ) => PostHelper::get_comment_status( $args ),
				'required_args' => [ 'post_id' ],
			],
			'is_sticky'           => [
				'label'         => __( 'Sticky', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'boolean',
				'description'   => __( 'Check if the post is sticky.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => PostHelper::is_sticky( $args ),
				'required_args' => [ 'post_id' ],
			],
			'has_excerpt'         => [
				'label'         => __( 'Excerpt', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'boolean',
				'description'   => __( 'Check if the post has a manual excerpt.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => PostHelper::has_excerpt( $args ),
				'required_args' => [ 'post_id' ],
			],
			'post_word_count'     => [
				'label'         => __( 'Word Count', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'number',
				'placeholder'   => __( 'e.g. 500', 'sugarcommerce' ),
				'min'           => 0,
				'step'          => 1,
				'description'   => __( 'The approximate word count of the post content.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => PostHelper::get_word_count( $args ),
				'required_args' => [ 'post_id' ],
			],
			'has_shortcode'       => [
				'label'         => __( 'Contains Shortcode', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'text',
				'placeholder'   => __( 'e.g. gallery', 'sugarcommerce' ),
				'description'   => __( 'Check if the post contains a specific shortcode.', 'sugarcommerce' ),
				'operators'     => Operators::contains(),
				'compare_value' => fn( $args, $user_value ) => PostHelper::has_shortcode( $args, $user_value ) ? $user_value : '',
				'required_args' => [ 'post_id' ],
			],
			'has_block'           => [
				'label'         => __( 'Contains Block', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'text',
				'placeholder'   => __( 'e.g. core/image', 'sugarcommerce' ),
				'description'   => __( 'Check if the post contains a specific Gutenberg block.', 'sugarcommerce' ),
				'operators'     => Operators::contains(),
				'compare_value' => fn( $args, $user_value ) => PostHelper::has_block( $args, $user_value ) ? $user_value : '',
				'required_args' => [ 'post_id' ],
			],
		];
	}

	/**
	 * Get meta-related conditions.
	 *
	 * @return array<string, array>
	 */
	private static function get_meta_conditions(): array {
		return [
			'post_meta_text'   => [
				'label'         => __( 'Post Meta (Text)', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'text',
				'placeholder'   => __( 'meta_key:value', 'sugarcommerce' ),
				'description'   => __( 'Format: meta_key:value_to_match', 'sugarcommerce' ),
				'user_value'    => fn( $value ) => Parse::meta( (string) $value )['value'],
				'compare_value' => fn( $args, $user_value ) => PostHelper::get_meta_text( $args, $user_value ),
				'required_args' => [ 'post_id' ],
			],
			'post_meta_number' => [
				'label'         => __( 'Post Meta (Number)', 'sugarcommerce' ),
				'group'         => __( 'Post', 'sugarcommerce' ),
				'type'          => 'text',
				'compare_as'    => 'number',
				'operators'     => Operators::numeric(),
				'placeholder'   => __( 'meta_key:value', 'sugarcommerce' ),
				'description'   => __( 'Match against a numeric post meta field. Format: meta_key:value', 'sugarcommerce' ),
				'user_value'    => fn( $value ) => Parse::meta( (string) $value )['value'],
				'compare_value' => fn( $args, $user_value ) => PostHelper::get_meta_number( $args, $user_value ),
				'required_args' => [ 'post_id' ],
			],
		];
	}
}
