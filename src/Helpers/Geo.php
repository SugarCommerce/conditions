<?php
/**
 * Geo Helper
 *
 * Geographic comparison helpers for fraud-detection conditions.
 *
 * @package     SugarCommerce\Conditions\Helpers
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Helpers;

use Locale;
use SugarCommerce\Conditions\Conditions;

/**
 * Class Geo
 *
 * Utilities for geo-mismatch conditions. The library does no geo-IP lookups:
 * countries arrive in the arguments, or from the `country` provider.
 */
final class Geo {

	/**
	 * Every ISO 3166-1 alpha-2 code in use.
	 *
	 * @var string
	 */
	private const string CODES = 'AD AE AF AG AI AL AM AO AQ AR AS AT AU AW AX AZ BA BB BD BE BF BG BH BI BJ BL BM BN BO BQ BR BS BT BV BW BY BZ CA CC CD CF CG CH CI CK CL CM CN CO CR CU CV CW CX CY CZ DE DJ DK DM DO DZ EC EE EG EH ER ES ET FI FJ FK FM FO FR GA GB GD GE GF GG GH GI GL GM GN GP GQ GR GS GT GU GW GY HK HM HN HR HT HU ID IE IL IM IN IO IQ IR IS IT JE JM JO JP KE KG KH KI KM KN KP KR KW KY KZ LA LB LC LI LK LR LS LT LU LV LY MA MC MD ME MF MG MH MK ML MM MN MO MP MQ MR MS MT MU MV MW MX MY MZ NA NC NE NF NG NI NL NO NP NR NU NZ OM PA PE PF PG PH PK PL PM PN PR PS PT PW PY QA RE RO RS RU RW SA SB SC SD SE SG SH SI SJ SK SL SM SN SO SR SS ST SV SX SY SZ TC TD TF TG TH TJ TK TL TM TN TO TR TT TV TW TZ UA UG UM US UY UZ VA VC VE VG VI VN VU WF WS XK YE YT ZA ZM ZW';

	/**
	 * Whether the visitor IP country matches the billing country.
	 *
	 * Returns false when either value is missing — most callers want a
	 * mismatch to look like "no, they don't match" rather than skip.
	 *
	 * @param array $args Condition args (`ip_country`, `billing_country`).
	 *
	 * @return bool
	 */
	public static function ip_matches_billing( array $args ): bool {
		return self::same( $args['ip_country'] ?? '', $args['billing_country'] ?? '' );
	}

	/**
	 * Whether the issuing-card country matches the billing country.
	 *
	 * @param array $args Condition args (`card_country`, `billing_country`).
	 *
	 * @return bool
	 */
	public static function card_matches_billing( array $args ): bool {
		return self::same( $args['card_country'] ?? '', $args['billing_country'] ?? '' );
	}

	/**
	 * Whether two country codes are the same country; false when either is missing.
	 *
	 * @param mixed $a One code.
	 * @param mixed $b The other.
	 *
	 * @return bool
	 */
	public static function same( mixed $a, mixed $b ): bool {
		$a = self::normalize( $a );
		$b = self::normalize( $b );

		return '' !== $a && '' !== $b && $a === $b;
	}

	/**
	 * Get the IP-to-billing distance in kilometres.
	 *
	 * The caller works it out -- with haversine() from two pairs of
	 * coordinates -- and passes it as `geo_distance_km`.
	 *
	 * @param array $args Condition args (`geo_distance_km`).
	 *
	 * @return float
	 */
	public static function get_distance_km( array $args ): float {
		return is_numeric( $args['geo_distance_km'] ?? null ) ? (float) $args['geo_distance_km'] : 0.0;
	}

	/**
	 * Compute great-circle distance between two coordinate pairs.
	 *
	 * @param float $lat1 Source latitude.
	 * @param float $lng1 Source longitude.
	 * @param float $lat2 Target latitude.
	 * @param float $lng2 Target longitude.
	 *
	 * @return float Distance in kilometres, to two places.
	 */
	public static function haversine( float $lat1, float $lng1, float $lat2, float $lng2 ): float {
		$earth_km = 6371.0;
		$d_lat    = deg2rad( $lat2 - $lat1 );
		$d_lng    = deg2rad( $lng2 - $lng1 );

		$a = sin( $d_lat / 2 ) ** 2
			+ cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $d_lng / 2 ) ** 2;

		return round( $earth_km * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) ), 2 );
	}

	/**
	 * Countries as [{value, label}, ...] for the country pickers.
	 *
	 * The names come from the `countries` provider when a plugin gave one,
	 * otherwise from ext-intl in the site's language, otherwise the codes
	 * stand for themselves. The library carries no list of names to keep
	 * up to date.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_country_options(): array {
		$names = Conditions::provided( 'countries' );

		if ( ! is_array( $names ) || empty( $names ) ) {
			$names = [];

			foreach ( explode( ' ', self::CODES ) as $code ) {
				$names[ $code ] = self::name( $code );
			}

			asort( $names, SORT_NATURAL | SORT_FLAG_CASE );
		}

		$out = [];

		foreach ( $names as $code => $name ) {
			$out[] = [
				'value' => strtoupper( (string) $code ),
				'label' => (string) $name,
			];
		}

		return $out;
	}

	/**
	 * A country's name, from ext-intl when it is there.
	 *
	 * @param string $code Two-letter code.
	 *
	 * @return string
	 */
	private static function name( string $code ): string {
		if ( ! class_exists( Locale::class ) ) {
			return $code;
		}

		$locale = function_exists( 'get_locale' ) ? (string) get_locale() : 'en';
		$name   = Locale::getDisplayRegion( 'und-' . $code, $locale );

		return is_string( $name ) && '' !== $name && $name !== $code ? $name : $code;
	}

	/**
	 * Normalize an ISO-2 country code (uppercase, trimmed).
	 *
	 * @param mixed $value Raw value from args.
	 *
	 * @return string
	 */
	public static function normalize( mixed $value ): string {
		return is_scalar( $value ) ? strtoupper( trim( (string) $value ) ) : '';
	}
}
