<?php
/**
 * Request Options Helper
 *
 * Option lists for the request conditions.
 *
 * @package     SugarCommerce\Conditions\Options
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Options;

/**
 * Class Request
 *
 * Request methods, browser languages and UTM parameters as
 * [{value, label}, ...].
 */
final class Request {

	/**
	 * Get HTTP request method options.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_methods(): array {
		return self::options( array_combine(
			[ 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS' ],
			[ 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS' ]
		) );
	}

	/**
	 * The languages a browser commonly asks for.
	 *
	 * A plain language matches every region of it -- `en` is any English
	 * browser -- and a tag with a region only that region.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_languages(): array {
		return self::options( [
			'en'    => __( 'English', 'sugarcommerce' ),
			'en-US' => __( 'English (US)', 'sugarcommerce' ),
			'en-GB' => __( 'English (UK)', 'sugarcommerce' ),
			'es'    => __( 'Spanish', 'sugarcommerce' ),
			'es-ES' => __( 'Spanish (Spain)', 'sugarcommerce' ),
			'es-MX' => __( 'Spanish (Mexico)', 'sugarcommerce' ),
			'fr'    => __( 'French', 'sugarcommerce' ),
			'fr-FR' => __( 'French (France)', 'sugarcommerce' ),
			'fr-CA' => __( 'French (Canada)', 'sugarcommerce' ),
			'de'    => __( 'German', 'sugarcommerce' ),
			'de-DE' => __( 'German (Germany)', 'sugarcommerce' ),
			'de-AT' => __( 'German (Austria)', 'sugarcommerce' ),
			'it'    => __( 'Italian', 'sugarcommerce' ),
			'pt'    => __( 'Portuguese', 'sugarcommerce' ),
			'pt-BR' => __( 'Portuguese (Brazil)', 'sugarcommerce' ),
			'pt-PT' => __( 'Portuguese (Portugal)', 'sugarcommerce' ),
			'nl'    => __( 'Dutch', 'sugarcommerce' ),
			'ru'    => __( 'Russian', 'sugarcommerce' ),
			'ja'    => __( 'Japanese', 'sugarcommerce' ),
			'zh'    => __( 'Chinese', 'sugarcommerce' ),
			'zh-CN' => __( 'Chinese (Simplified)', 'sugarcommerce' ),
			'zh-TW' => __( 'Chinese (Traditional)', 'sugarcommerce' ),
			'ko'    => __( 'Korean', 'sugarcommerce' ),
			'ar'    => __( 'Arabic', 'sugarcommerce' ),
			'hi'    => __( 'Hindi', 'sugarcommerce' ),
			'tr'    => __( 'Turkish', 'sugarcommerce' ),
			'pl'    => __( 'Polish', 'sugarcommerce' ),
			'sv'    => __( 'Swedish', 'sugarcommerce' ),
			'da'    => __( 'Danish', 'sugarcommerce' ),
			'no'    => __( 'Norwegian', 'sugarcommerce' ),
			'fi'    => __( 'Finnish', 'sugarcommerce' ),
			'el'    => __( 'Greek', 'sugarcommerce' ),
			'he'    => __( 'Hebrew', 'sugarcommerce' ),
			'th'    => __( 'Thai', 'sugarcommerce' ),
			'vi'    => __( 'Vietnamese', 'sugarcommerce' ),
			'id'    => __( 'Indonesian', 'sugarcommerce' ),
			'uk'    => __( 'Ukrainian', 'sugarcommerce' ),
			'cs'    => __( 'Czech', 'sugarcommerce' ),
			'ro'    => __( 'Romanian', 'sugarcommerce' ),
			'hu'    => __( 'Hungarian', 'sugarcommerce' ),
		] );
	}

	/**
	 * The UTM parameters, for a rule's unit.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	public static function get_utm_parameters(): array {
		return self::options( [
			'source'   => __( 'Source (utm_source)', 'sugarcommerce' ),
			'medium'   => __( 'Medium (utm_medium)', 'sugarcommerce' ),
			'campaign' => __( 'Campaign (utm_campaign)', 'sugarcommerce' ),
			'term'     => __( 'Term (utm_term)', 'sugarcommerce' ),
			'content'  => __( 'Content (utm_content)', 'sugarcommerce' ),
		] );
	}

	/**
	 * value => label pairs as [{value, label}, ...].
	 *
	 * @param array<string, string> $pairs The pairs.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	private static function options( array $pairs ): array {
		$out = [];

		foreach ( $pairs as $value => $label ) {
			$out[] = [
				'value' => (string) $value,
				'label' => $label,
			];
		}

		return $out;
	}
}
