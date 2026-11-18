<?php
/**
 * User Built-in Conditions
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
use SugarCommerce\Conditions\Helpers\User as UserHelper;
use SugarCommerce\Conditions\Options\WordPress;
use SugarCommerce\Conditions\Options\Periods;
use SugarCommerce\Conditions\Operators;

/**
 * Class User
 *
 * Provides user related conditions.
 */
final class User {

	/**
	 * Get all user conditions.
	 *
	 * @return array<string, array>
	 */
	public static function get_all(): array {
		return [
			// Identity
			'is_logged_in'       => [
				'label'         => __( 'Logged In', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'boolean',
				'description'   => __( 'Check if the user is logged in.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => $args['is_logged_in'] ?? is_user_logged_in(),
				'required_args' => [],
			],
			'user_id'            => [
				'label'         => __( 'Specific User', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'user',
				'multiple'      => true,
				'placeholder'   => __( 'Search users...', 'sugarcommerce' ),
				'description'   => __( 'Match against specific user(s).', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => UserHelper::get_id( $args ),
				'required_args' => [],
			],
			'user_role'          => [
				'label'         => __( 'Role', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select roles...', 'sugarcommerce' ),
				'description'   => __( 'Match against the user\'s role(s).', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => WordPress::get_roles(),
				'compare_value' => fn( $args ) => UserHelper::get_roles( $args ),
				'required_args' => [],
			],
			'has_capability'     => [
				'label'         => __( 'Has Capability', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select capabilities...', 'sugarcommerce' ),
				'description'   => __( 'Check if the user has specific capabilities.', 'sugarcommerce' ),
				'operators'     => Operators::collection(),
				'options'       => fn() => WordPress::get_capabilities(),
				'compare_value' => fn( $args, $asked = null ) => UserHelper::get_capabilities( $args, $asked ),
				'required_args' => [],
			],

			// Profile
			'user_email'         => [
				'label'         => __( 'Email', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'email',
				'placeholder'   => __( 'e.g. john@test.com, @gmail.com, .edu', 'sugarcommerce' ),
				'description'   => __( 'Match against email patterns. Supports: full email, @domain, .tld, or domain.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => UserHelper::get_email( $args ),
				'required_args' => [],
			],
			'user_username'      => [
				'label'         => __( 'Username', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'text',
				'placeholder'   => __( 'e.g. johndoe', 'sugarcommerce' ),
				'description'   => __( 'Match against the user\'s login username.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => UserHelper::get_username( $args ),
				'required_args' => [],
			],
			'user_display_name'  => [
				'label'         => __( 'Display Name', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'text',
				'placeholder'   => __( 'e.g. John Doe', 'sugarcommerce' ),
				'description'   => __( 'Match against the user\'s display name.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => UserHelper::get_display_name( $args ),
				'required_args' => [],
			],

			'user_first_name'    => [
				'label'         => __( 'First Name', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'text',
				'placeholder'   => __( 'e.g. John', 'sugarcommerce' ),
				'description'   => __( 'Match against the first name on the user\'s profile.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => UserHelper::get_first_name( $args ),
				'required_args' => [],
			],
			'user_last_name'     => [
				'label'         => __( 'Last Name', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'text',
				'placeholder'   => __( 'e.g. Doe', 'sugarcommerce' ),
				'description'   => __( 'Match against the last name on the user\'s profile.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => UserHelper::get_last_name( $args ),
				'required_args' => [],
			],
			'user_nicename'      => [
				'label'         => __( 'Nicename (Slug)', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'text',
				'placeholder'   => __( 'e.g. john-doe', 'sugarcommerce' ),
				'description'   => __( 'The URL-safe slug WordPress derives from the username. Distinct from the login, which is what the Username condition matches.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => UserHelper::get_nicename( $args ),
				'required_args' => [],
			],
			'user_website'       => [
				'label'         => __( 'Website', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'text',
				'placeholder'   => __( 'e.g. example.com', 'sugarcommerce' ),
				'description'   => __( 'The website URL on the user\'s profile. Spam registrations fill this in far more often than real ones leave it blank.', 'sugarcommerce' ),
				'operators'     => Operators::text_advanced(),
				'compare_value' => fn( $args ) => UserHelper::get_website( $args ),
				'required_args' => [],
			],
			'user_locale'        => [
				'label'         => __( 'Locale', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'text',
				'placeholder'   => __( 'e.g. en_GB', 'sugarcommerce' ),
				'description'   => __( 'The user\'s admin language, falling back to the site locale when they have not chosen one.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => UserHelper::get_locale( $args ),
				'required_args' => [],
			],
			'is_super_admin'     => [
				'label'         => __( 'Super Admin', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'boolean',
				'description'   => __( 'Whether the user is a network super admin. Always false on a single site — use the Role condition there.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => UserHelper::is_super_admin( $args ),
				'required_args' => [],
			],

			// Dates
			'user_date_registered' => [
				'label'         => __( 'Date Registered', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'date',
				'description'   => __( 'Match against the user registration date.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => UserHelper::get_date_registered( $args ),
				'required_args' => [],
			],
			'user_registered'    => [
				'label'         => __( 'Account Age', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'number_unit',
				'placeholder'   => __( 'e.g. 30', 'sugarcommerce' ),
				'description'   => __( 'How long the user has been registered.', 'sugarcommerce' ),
				'min'           => 0,
				'units'         => Periods::get_age_units(),
				'compare_value' => fn( $args ) => UserHelper::get_age( $args ),
				'required_args' => [],
			],

			// Activity
			'user_post_count'    => [
				'label'         => __( 'Post Count', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'number',
				'placeholder'   => __( 'e.g. 10', 'sugarcommerce' ),
				'min'           => 0,
				'step'          => 1,
				'description'   => __( 'The number of posts authored by the user.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => UserHelper::get_post_count( $args ),
				'required_args' => [],
			],
			'user_comment_count' => [
				'label'         => __( 'Comment Count', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'number',
				'placeholder'   => __( 'e.g. 25', 'sugarcommerce' ),
				'min'           => 0,
				'step'          => 1,
				'description'   => __( 'The number of approved comments by the user.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => UserHelper::get_comment_count( $args ),
				'required_args' => [],
			],

			// Meta
			'user_meta_text'     => [
				'label'         => __( 'User Meta (Text)', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'text',
				'placeholder'   => __( 'meta_key:value', 'sugarcommerce' ),
				'description'   => __( 'Format: meta_key:value_to_match', 'sugarcommerce' ),
				'user_value'    => fn( $value ) => Parse::meta( (string) $value )['value'],
				'compare_value' => fn( $args, $user_value ) => UserHelper::get_meta_text( $args, $user_value ),
				'required_args' => [],
			],
			'user_meta_number'   => [
				'label'         => __( 'User Meta (Number)', 'sugarcommerce' ),
				'group'         => __( 'User', 'sugarcommerce' ),
				'type'          => 'text',
				'compare_as'    => 'number',
				'operators'     => Operators::numeric(),
				'placeholder'   => __( 'meta_key:value', 'sugarcommerce' ),
				'description'   => __( 'Match against a numeric user meta field. Format: meta_key:value', 'sugarcommerce' ),
				'user_value'    => fn( $value ) => Parse::meta( (string) $value )['value'],
				'compare_value' => fn( $args, $user_value ) => UserHelper::get_meta_number( $args, $user_value ),
				'required_args' => [],
			],
		];
	}
}
