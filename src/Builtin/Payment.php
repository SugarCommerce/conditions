<?php
/**
 * Payment Conditions
 *
 * Card-fingerprint velocity, card funding type, and gateway-provided risk
 * signals (e.g. Stripe Radar). All values are post-tokenization, so these
 * conditions only fire during a post-payment "review" pass — pre-checkout
 * the data does not exist.
 *
 * Callers MUST pass `card_fingerprint`, `card_funding`, `stripe_risk_level`,
 * etc. through the args array. The library performs no gateway calls itself.
 *
 * @package     SugarCommerce\Conditions\Builtin
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Builtin;

use SugarCommerce\Conditions\Helpers\Velocity as VelocityHelper;
use SugarCommerce\Conditions\Operators;
use SugarCommerce\Conditions\Options\Periods;

/**
 * Class Payment
 *
 * Provides card-fingerprint velocity and gateway-risk conditions.
 */
final class Payment {

	/**
	 * Get all payment conditions.
	 *
	 * @return array<string, array>
	 */
	public static function get_all(): array {
		$group = __( 'Payment', 'sugarcommerce' );

		return [
			'card_funding_type'                      => [
				'label'         => __( 'Card Funding Type', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select funding types...', 'sugarcommerce' ),
				'description'   => __( 'How the card is funded — credit, debit, prepaid, or unknown. Returned by the gateway after tokenisation. Prepaid cards are disproportionately used in fraud (anonymous, hard to recover charges from). "Is any of: prepaid" is a common review-rule trigger.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => self::get_funding_types(),
				'compare_value' => fn( $args ) => strtolower( (string) ( $args['card_funding'] ?? '' ) ),
				'required_args' => [ 'card_funding' ],
			],
			'card_brand'                             => [
				'label'         => __( 'Card Brand', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select brands...', 'sugarcommerce' ),
				'description'   => __( 'Card network — Visa, Mastercard, Amex, Discover, etc. Mostly informational; useful for restricting payments to specific brands or for region-specific rules (e.g. Discover is rarely used outside the US, so a Discover card with non-US billing is unusual).', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => self::get_card_brands(),
				'compare_value' => fn( $args ) => strtolower( (string) ( $args['card_brand'] ?? '' ) ),
				'required_args' => [ 'card_brand' ],
			],
			'stripe_risk_level'                      => [
				'label'         => __( 'Stripe Risk Level', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select risk levels...', 'sugarcommerce' ),
				'description'   => __( 'Stripe Radar\'s ML verdict — Normal (low risk), Elevated (suspicious), Highest (likely fraud). Stripe applies this automatically on every charge using their global fraud network. "Highest" is a strong block trigger; "Elevated" is a typical review trigger.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => self::get_stripe_risk_levels(),
				'compare_value' => fn( $args ) => strtolower( (string) ( $args['stripe_risk_level'] ?? '' ) ),
				'required_args' => [ 'stripe_risk_level' ],
			],
			'stripe_risk_score'                      => [
				'label'         => __( 'Stripe Risk Score', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'number',
				'placeholder'   => __( 'e.g. 65', 'sugarcommerce' ),
				'min'           => 0,
				'max'           => 100,
				'step'          => 1,
				'description'   => __( 'Stripe Radar\'s numeric risk score (0-100). Stripe maps to risk levels at ≥65 = Elevated, ≥75 = Highest. Use the score directly when you want finer thresholds than the level dropdown allows.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => (int) ( $args['stripe_risk_score'] ?? 0 ),
				'required_args' => [ 'stripe_risk_score' ],
			],
			'square_risk_level'                      => [
				'label'         => __( 'Square Risk Level', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select risk levels...', 'sugarcommerce' ),
				'description'   => __( 'Square\'s own risk verdict on the payment — Pending (still evaluating), Normal (low risk), Moderate (suspicious), High (likely fraud). Square applies this automatically on every payment. "High" is a strong block trigger; "Moderate" a typical review trigger.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => self::get_square_risk_levels(),
				'compare_value' => fn( $args ) => strtolower( (string) ( $args['square_risk_level'] ?? '' ) ),
				'required_args' => [ 'square_risk_level' ],
			],
			'square_avs_status'                      => [
				'label'         => __( 'Square AVS Status', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select AVS results...', 'sugarcommerce' ),
				'description'   => __( 'Square\'s AVS (Address Verification System) result — accepted, rejected, or not checked. Rejected AVS on a high-value order is a strong review signal.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => self::get_square_avs_cvv_options(),
				'compare_value' => fn( $args ) => strtoupper( (string) ( $args['square_avs_status'] ?? '' ) ),
				'required_args' => [ 'square_avs_status' ],
			],
			'square_cvv_status'                      => [
				'label'         => __( 'Square CVV Status', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select CVV results...', 'sugarcommerce' ),
				'description'   => __( 'Square\'s CVV result — accepted, rejected, or not checked. Rejected CVV is highly correlated with stolen-card use; "not checked" usually means contactless or a card-on-file payment.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => self::get_square_avs_cvv_options(),
				'compare_value' => fn( $args ) => strtoupper( (string) ( $args['square_cvv_status'] ?? '' ) ),
				'required_args' => [ 'square_cvv_status' ],
			],
			'velocity_orders_by_card_fingerprint'    => [
				'label'         => __( 'Orders by Card Fingerprint', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'number_unit',
				'placeholder'   => __( 'e.g. 3', 'sugarcommerce' ),
				'min'           => 0,
				'step'          => 1,
				'units'         => Periods::get_window_units(),
				'description'   => __( 'How many orders have used this exact card in the chosen time window. Higher than 1-2 in 24 hours is a strong stolen-card signal — fraudsters often run a stolen card repeatedly until the issuer blocks it. The card "fingerprint" is a salted hash of brand + last4 + country, so the lib never stores raw card data.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => VelocityHelper::count( 'orders_by_card_fingerprint', $args ),
				'required_args' => [ 'card_fingerprint' ],
			],
			'velocity_distinct_emails_by_card_fingerprint' => [
				'label'         => __( 'Distinct Emails per Card', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'number_unit',
				'placeholder'   => __( 'e.g. 2', 'sugarcommerce' ),
				'min'           => 0,
				'step'          => 1,
				'units'         => Periods::get_window_units(),
				'description'   => __( 'How many DIFFERENT email addresses have used this card in the chosen time window. The strongest stolen-card fingerprint — a real customer doesn\'t buy under three different emails on the same card. Suggested threshold: ≥2 distinct emails in 24 hours = block.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => VelocityHelper::count( 'distinct_emails_by_card_fingerprint', $args ),
				'required_args' => [ 'card_fingerprint' ],
			],

			// PayPal Orders v2 capture-time signals. Available post-payment
			// only — callers are expected to populate these from a PayPal
			// webhook handler (PAYMENT.CAPTURE.COMPLETED) on the order.
			'paypal_seller_protection'   => [
				'label'         => __( 'PayPal Seller Protection', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select levels...', 'sugarcommerce' ),
				'description'   => __( 'PayPal\'s own verdict on whether YOU are protected from chargebacks if this transaction goes wrong. ELIGIBLE = full protection, PARTIALLY_ELIGIBLE = covered for unauthorised use only, NOT_ELIGIBLE = no protection. NOT_ELIGIBLE on a high-value order is a strong review trigger — PayPal already flagged it.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => self::get_paypal_seller_protection_levels(),
				'compare_value' => fn( $args ) => strtoupper( (string) ( $args['paypal_seller_protection'] ?? '' ) ),
				'required_args' => [ 'paypal_seller_protection' ],
			],
			'paypal_avs_code'            => [
				'label'         => __( 'PayPal AVS Code', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'tags',
				'placeholder'   => __( 'e.g. N, A, Z (mismatch codes)', 'sugarcommerce' ),
				'operators'     => Operators::tags_exact(),
				'description'   => __( 'Single-letter AVS response from the card network. Y/D/F/M/X = the billing address matches what the issuer has on file (good). N/A/Z = mismatch (suspicious — fraudster doesn\'t know the real cardholder\'s address). To flag mismatches, use "Is any of: N, A, Z".', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => strtoupper( (string) ( $args['paypal_avs_code'] ?? '' ) ),
				'required_args' => [ 'paypal_avs_code' ],
			],
			'paypal_cvv_code'            => [
				'label'         => __( 'PayPal CVV Code', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'tags',
				'placeholder'   => __( 'e.g. N, P (no match / not processed)', 'sugarcommerce' ),
				'operators'     => Operators::tags_exact(),
				'description'   => __( 'Single-letter CVV response from the card network. M = match (good). N = no match (very strong fraud signal — fraudster guessed the CVV). P/S/U = not processed/should have been provided/unavailable (less clear-cut). To flag mismatches, use "Is any of: N".', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => strtoupper( (string) ( $args['paypal_cvv_code'] ?? '' ) ),
				'required_args' => [ 'paypal_cvv_code' ],
			],
			'paypal_payer_country'       => [
				'label'         => __( 'PayPal Payer Country', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'select',
				'multiple'      => true,
				'placeholder'   => __( 'Select countries...', 'sugarcommerce' ),
				'description'   => __( 'Country the PayPal account is registered in (set when the buyer signed up, doesn\'t change with travel/VPN). Stronger than IP country for cross-region fraud detection. Match against a watchlist or compare against billing country.', 'sugarcommerce' ),
				'operators'     => Operators::collection_any_none(),
				'options'       => fn() => \SugarCommerce\Conditions\Helpers\Geo::get_country_options(),
				'compare_value' => fn( $args ) => strtoupper( (string) ( $args['paypal_payer_country'] ?? '' ) ),
				'required_args' => [ 'paypal_payer_country' ],
			],
			'paypal_payer_country_matches_billing' => [
				'label'         => __( 'PayPal Payer Country Matches Billing', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'boolean',
				'description'   => __( 'True when the PayPal account country matches the billing-address country. Stronger fraud signal than IP-vs-billing because the PayPal account country is verified by PayPal at signup and isn\'t affected by VPNs. Combine with operator "no" to flag mismatches.', 'sugarcommerce' ),
				'compare_value' => function ( $args ) {
					$payer   = strtoupper( (string) ( $args['paypal_payer_country'] ?? '' ) );
					$billing = strtoupper( (string) ( $args['billing_country'] ?? '' ) );

					return $payer !== '' && $billing !== '' && $payer === $billing;
				},
				'required_args' => [ 'paypal_payer_country', 'billing_country' ],
			],
			'paypal_decline_reason'      => [
				'label'         => __( 'PayPal Decline Reason', 'sugarcommerce' ),
				'group'         => $group,
				'type'          => 'tags',
				'placeholder'   => __( 'e.g. DECLINED_BY_RISK_FRAUD_FILTERS', 'sugarcommerce' ),
				'operators'     => Operators::tags_exact(),
				'description'   => __( 'PayPal\'s decline reason from the capture\'s status_details. The signal you care most about is DECLINED_BY_RISK_FRAUD_FILTERS — PayPal\'s own ML caught the transaction, which is high-confidence fraud. Other values like INSUFFICIENT_FUNDS or PAYER_CANNOT_PAY are not fraud signals.', 'sugarcommerce' ),
				'compare_value' => fn( $args ) => strtoupper( (string) ( $args['paypal_decline_reason'] ?? '' ) ),
				'required_args' => [ 'paypal_decline_reason' ],
			],
		];
	}

	/**
	 * PayPal seller_protection.status values.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	private static function get_paypal_seller_protection_levels(): array {
		return [
			[
				'value' => 'ELIGIBLE',
				'label' => __( 'Eligible', 'sugarcommerce' ),
			],
			[
				'value' => 'PARTIALLY_ELIGIBLE',
				'label' => __( 'Partially Eligible', 'sugarcommerce' ),
			],
			[
				'value' => 'NOT_ELIGIBLE',
				'label' => __( 'Not Eligible', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Card funding type options.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	private static function get_funding_types(): array {
		return [
			[
				'value' => 'credit',
				'label' => __( 'Credit', 'sugarcommerce' ),
			],
			[
				'value' => 'debit',
				'label' => __( 'Debit', 'sugarcommerce' ),
			],
			[
				'value' => 'prepaid',
				'label' => __( 'Prepaid', 'sugarcommerce' ),
			],
			[
				'value' => 'unknown',
				'label' => __( 'Unknown', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Common card brand options (Stripe-style identifiers).
	 *
	 * @return array<array{value: string, label: string}>
	 */
	private static function get_card_brands(): array {
		return [
			[
				'value' => 'visa',
				'label' => __( 'Visa', 'sugarcommerce' ),
			],
			[
				'value' => 'mastercard',
				'label' => __( 'Mastercard', 'sugarcommerce' ),
			],
			[
				'value' => 'amex',
				'label' => __( 'American Express', 'sugarcommerce' ),
			],
			[
				'value' => 'discover',
				'label' => __( 'Discover', 'sugarcommerce' ),
			],
			[
				'value' => 'diners',
				'label' => __( 'Diners Club', 'sugarcommerce' ),
			],
			[
				'value' => 'jcb',
				'label' => __( 'JCB', 'sugarcommerce' ),
			],
			[
				'value' => 'unionpay',
				'label' => __( 'UnionPay', 'sugarcommerce' ),
			],
			[
				'value' => 'unknown',
				'label' => __( 'Unknown', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Stripe Radar risk level options.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	private static function get_stripe_risk_levels(): array {
		return [
			[
				'value' => 'normal',
				'label' => __( 'Normal', 'sugarcommerce' ),
			],
			[
				'value' => 'elevated',
				'label' => __( 'Elevated', 'sugarcommerce' ),
			],
			[
				'value' => 'highest',
				'label' => __( 'Highest', 'sugarcommerce' ),
			],
			[
				'value' => 'not_assessed',
				'label' => __( 'Not Assessed', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Square risk level options.
	 *
	 * Values match Square's `risk_evaluation.risk_level` payload
	 * shape verbatim — uppercase. Comparison in the rule editor
	 * normalises both sides to lowercase, so the dropdown shows
	 * proper casing while the underlying value matches the API.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	private static function get_square_risk_levels(): array {
		return [
			[
				'value' => 'pending',
				'label' => __( 'Pending', 'sugarcommerce' ),
			],
			[
				'value' => 'normal',
				'label' => __( 'Normal', 'sugarcommerce' ),
			],
			[
				'value' => 'moderate',
				'label' => __( 'Moderate', 'sugarcommerce' ),
			],
			[
				'value' => 'high',
				'label' => __( 'High', 'sugarcommerce' ),
			],
		];
	}

	/**
	 * Square AVS / CVV status options.
	 *
	 * Same enum used for both fields — Square returns
	 * `AVS_ACCEPTED` / `AVS_REJECTED` / `AVS_NOT_CHECKED` and the
	 * matching CVV trio.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	private static function get_square_avs_cvv_options(): array {
		return [
			[
				'value' => 'AVS_ACCEPTED',
				'label' => __( 'AVS / CVV Accepted', 'sugarcommerce' ),
			],
			[
				'value' => 'AVS_REJECTED',
				'label' => __( 'AVS / CVV Rejected', 'sugarcommerce' ),
			],
			[
				'value' => 'AVS_NOT_CHECKED',
				'label' => __( 'AVS / CVV Not Checked', 'sugarcommerce' ),
			],
			[
				'value' => 'CVV_ACCEPTED',
				'label' => __( 'CVV Accepted', 'sugarcommerce' ),
			],
			[
				'value' => 'CVV_REJECTED',
				'label' => __( 'CVV Rejected', 'sugarcommerce' ),
			],
			[
				'value' => 'CVV_NOT_CHECKED',
				'label' => __( 'CVV Not Checked', 'sugarcommerce' ),
			],
		];
	}
}
