<?php
/**
 * Email Pattern Conditions
 *
 * Generic email-pattern conditions usable for any visitor email — not tied
 * to a particular customer record. Needs nothing but WordPress.
 *
 * @package     SugarCommerce\Conditions\Builtin
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Builtin;

use SugarCommerce\Conditions\Helpers\Email as EmailHelper;
use SugarCommerce\Conditions\Operators;

/**
 * Class Email
 *
 * Provides email-pattern conditions.
 */
final class Email {

	/**
	 * Get all email conditions.
	 *
	 * @return array<string, array>
	 */
	public static function get_all(): array {
		$group = __( 'Email', 'sugarcommerce' );

		return [
			'email_is_freemail'           => [
				'label'         => __( 'Free Email Provider', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'boolean',
				'description'   => __( 'True when the email is from a free webmail provider — Gmail, Yahoo, Hotmail/Outlook, iCloud, Proton, AOL, mail.com, etc. Most fraudsters use freemail because it\'s anonymous and free; legitimate B2B customers tend to use their own domain. Strong combined signal — not enough to block alone, but powerful when paired with high cart total or new customer.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => EmailHelper::is_freemail( $args ),
				'required_args' => [ 'email' ],
			],
			'email_domain'                => [
				'label'         => __( 'Email Domain', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'tags',
				'placeholder'   => __( 'e.g. mailinator.com, proton.me', 'sugarcommerce' ),
				'operators'     => Operators::tags_exact(),
				'description'   => __( 'Match the email\'s domain against a list. Useful for blocking specific known-fraud providers (mailinator.com, tempmail.com) or whitelisting your customer\'s known corporate domains. Use "Is any of" for allowlists, "Is none of" for blocklists.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => EmailHelper::get_domain( $args ),
				'required_args' => [ 'email' ],
			],
			'email_has_plus_alias'        => [
				'label'         => __( 'Plus-Alias Email', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'boolean',
				'description'   => __( 'True when the email uses sub-addressing — `alice+promo@gmail.com` for example. Most providers route `+anything` back to the base inbox, which fraudsters exploit to create "different" emails that all reach the same mailbox. Soft signal — many legitimate customers use this for organisation. Pair with another signal.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => EmailHelper::has_plus_alias( $args ),
				'required_args' => [ 'email' ],
			],
			'email_local_suspicion_score' => [
				'label'         => __( 'Email Risk Score', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'number',
				'placeholder'   => __( 'e.g. 60', 'sugarcommerce' ),
				'min'           => 0,
				'max'           => 100,
				'step'          => 1,
				'description'   => __( 'Composite 0-100 score for the local-part of the email. Combines digit density (auto-generated emails skew numeric), length (>16 chars suspicious), plus-aliasing, multiple dots, and excessive specials. Suggested thresholds: <30 = legit, 30-59 = borderline (pair with another signal), 60-79 = review, ≥80 = likely auto-generated/block. Better than checking each sub-signal individually.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => EmailHelper::get_local_suspicion_score( $args ),
				'required_args' => [ 'email' ],
			],

			// Granular signals — kept for power users / consumer-plugin filters.
			'email_tld'                   => [
				'label'         => __( 'Email TLD', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'tags',
				'placeholder'   => __( 'e.g. cc, club, xyz, top', 'sugarcommerce' ),
				'operators'     => Operators::tags_exact(),
				'description'   => __( 'Match the email\'s top-level domain (the bit after the last dot — `com`, `uk`, `xyz`; a compound like `co.uk` reads as `uk`). Some TLDs correlate strongly with abuse: `.cc`, `.club`, `.xyz`, `.top`, `.tk`, `.ml`, `.work` are all common in fraud. Use "Is any of" with that watchlist for a quick block.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => EmailHelper::get_tld( $args ),
				'required_args' => [ 'email' ],
			],
			'email_local_length'          => [
				'label'         => __( 'Email Local Part Length', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'number',
				'placeholder'   => __( 'e.g. 20', 'sugarcommerce' ),
				'min'           => 0,
				'step'          => 1,
				'description'   => __( 'Character count of the local-part (everything before the @). Real names are usually under 16 characters; auto-generated fraud emails skew much longer (`john4827263abc8273@gmail.com` style). Suggested threshold: ≥ 20 chars is suspicious. The Suspicion Score condition rolls this in alongside other signals.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => EmailHelper::get_local_length( $args ),
				'required_args' => [ 'email' ],
			],
			'email_local_digit_density'   => [
				'label'         => __( 'Local Part Digit Density (%)', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'number',
				'placeholder'   => __( 'e.g. 50', 'sugarcommerce' ),
				'min'           => 0,
				'max'           => 100,
				'step'          => 1,
				'description'   => __( 'Percentage of the local-part that is digits (0-100). Real names are mostly letters; auto-generated fraud emails are mostly numbers. Suggested threshold: ≥ 50% digits = suspicious. The Suspicion Score condition rolls this in alongside other signals.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => (int) round( EmailHelper::get_local_digit_density( $args ) * 100 ),
				'required_args' => [ 'email' ],
			],
		];
	}
}
