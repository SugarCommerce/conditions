<?php
/**
 * The User Agent
 *
 * @package SugarCommerce\Conditions\Helpers
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Traits\Helpers\Request;

use SugarCommerce\Network\UserAgent as Agents;

/**
 * What a visitor's user agent says: empty, headless, or an outdated
 * browser.
 */
trait UserAgent {

	/**
	 * Get the raw user-agent string.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return string
	 */
	public static function get_user_agent( array $args = [] ): string {
		if ( isset( $args['user_agent'] ) ) {
			return (string) $args['user_agent'];
		}

		return Agents::current();
	}

	/**
	 * Whether the user-agent header is missing or empty.
	 *
	 * Legitimate browsers always send one. Empty UAs are usually scripts.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return bool
	 */
	public static function is_user_agent_empty( array $args = [] ): bool {
		return '' === trim( self::get_user_agent( $args ) );
	}

	/**
	 * Whether the user-agent looks like a headless browser or automation tool.
	 *
	 * Detects HeadlessChrome, PhantomJS, Selenium, Puppeteer, Playwright,
	 * Cypress, Nightmare, and similar automation framework signatures.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return bool
	 */
	public static function is_user_agent_headless( array $args = [] ): bool {
		$ua = self::get_user_agent( $args );

		if ( '' === $ua ) {
			return false;
		}

		$signatures = [
			'HeadlessChrome',
			'PhantomJS',
			'SlimerJS',
			'Selenium',
			'WebDriver',
			'puppeteer',
			'Playwright',
			'Cypress',
			'Nightmare',
			'jsdom',
			'Lighthouse',
		];

		foreach ( $signatures as $sig ) {
			if ( false !== stripos( $ua, $sig ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the user-agent advertises an outdated browser version.
	 *
	 * Heuristic only — version thresholds were chosen to flag genuinely
	 * abandoned releases without snagging modern enterprise builds.
	 *
	 * @param array $args The condition arguments.
	 *
	 * @return bool
	 */
	public static function is_user_agent_outdated_browser( array $args = [] ): bool {
		$ua = self::get_user_agent( $args );

		if ( '' === $ua ) {
			return false;
		}

		// Internet Explorer (any version is now outdated).
		if ( preg_match( '/MSIE \d|Trident\//i', $ua ) ) {
			return true;
		}

		// Chrome < 90. Edge and other Chromium browsers advertise Chrome too --
		// only match when there is no Edg/OPR/YaBrowser token alongside.
		if ( preg_match( '/Chrome\/(\d+)/i', $ua, $m ) && (int) $m[1] < 90
			&& ! preg_match( '/Edg(e|A|iOS)?\/|OPR\/|YaBrowser\//i', $ua ) ) {
			return true;
		}

		// Firefox < 78.
		if ( preg_match( '/Firefox\/(\d+)/i', $ua, $m ) && (int) $m[1] < 78 ) {
			return true;
		}

		// Safari < 13 (read the WebKit-style version token). Not on Android
		// or alongside a Chrome token: an Android WebView and every in-app
		// browser advertise "Version/4.0 ... Chrome/124 ... Safari", which is
		// not an old Safari, it is a current Chrome.
		return ! preg_match( '/Chrome\/|CriOS\/|Android/i', $ua )
			&& preg_match( '/Version\/(\d+).*Safari/i', $ua, $m ) && (int) $m[1] < 13;
	}
}
