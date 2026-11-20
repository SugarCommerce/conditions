<?php
/**
 * What the request helper believes about the request.
 *
 * @package SugarCommerce\Conditions
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions\Tests\Helpers;

use SugarCommerce\Conditions\Conditions;
use SugarCommerce\Conditions\Helpers\Request;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestHelperTest
 */
final class RequestTest extends TestCase {

	/**
	 * The server variables as they were.
	 *
	 * @var array
	 */
	private array $server;

	protected function setUp(): void {
		$this->server = $_SERVER;
		Conditions::reset();
		wpc_reset();
	}

	protected function tearDown(): void {
		$_SERVER = $this->server;
		Conditions::reset();
	}

	/**
	 * A country comes from the caller or a provider, never a header.
	 *
	 * The old helper read CF-IPCountry when the request came through a
	 * trusted proxy; this library has no list of proxies, so a header any
	 * visitor can send is not read at all. A provider that checks who
	 * connected answers instead.
	 */
	public function test_a_country_comes_from_the_caller_or_a_provider(): void {
		$_SERVER['HTTP_CF_IPCOUNTRY'] = 'US';
		$_SERVER['REMOTE_ADDR']       = '104.16.1.1';

		$this->assertNull( Request::get_country(), 'A header was believed.' );

		Conditions::provide( 'country', static fn(): string => 'de' );
		$this->assertSame( 'DE', Request::get_country() );

		foreach ( [ 'XX', 'T1', 'INVALID123', '' ] as $not_a_country ) {
			Conditions::provide( 'country', static fn(): string => $not_a_country );
			$this->assertNull( Request::get_country(), "$not_a_country was taken as a country." );
		}

		$this->assertSame( 'GB', Request::get_country( [ 'country' => 'GB' ] ), 'A country the caller knows wins.' );
	}

	/**
	 * The IP is the caller's, the provider's, or REMOTE_ADDR -- never a
	 * forwarding header.
	 */
	public function test_the_ip_is_never_read_from_a_forwarding_header(): void {
		$_SERVER['REMOTE_ADDR']          = '198.51.100.7';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1';

		$this->assertSame( '198.51.100.7', Request::get_ip() );
		$this->assertSame( '192.0.2.1', Request::get_ip( [ 'ip_address' => '192.0.2.1' ] ) );

		Conditions::provide( 'ip', static fn(): string => '203.0.113.50' );
		$this->assertSame( '203.0.113.50', Request::get_ip() );

		Conditions::provide( 'ip', static fn() => null );
		$this->assertNull( Request::get_ip(), 'A provider with no answer fell back to the proxy.' );
	}

	/**
	 * A browser's languages, best first, each with its plain language.
	 */
	public function test_languages_are_read_best_first_with_their_plain_language(): void {
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr;q=0.5, en-gb, en;q=0.8, de;q=0';

		$this->assertSame( [ 'en-GB', 'en', 'fr' ], Request::get_languages() );
		$this->assertSame( [ 'pt-BR', 'pt' ], Request::get_languages( [ 'accept_language' => 'pt-BR' ] ) );
		$this->assertSame( [], Request::get_languages( [ 'accept_language' => '*' ] ) );
	}

	/**
	 * A UTM parameter is read from the landing URL, and only a real one.
	 */
	public function test_a_utm_parameter_is_read_from_the_landing_url(): void {
		$_GET['utm_source'] = 'newsletter';
		$_GET['utm_secret'] = 'x';

		$this->assertSame( 'newsletter', Request::get_utm_parameter( [ '_unit' => 'source' ] ) );
		$this->assertSame( '', Request::get_utm_parameter( [ '_unit' => 'secret' ] ) );
		$this->assertSame( 'spring', Request::get_utm_parameter( [ '_unit' => 'campaign', 'utm_campaign' => 'spring' ] ) );

		unset( $_GET['utm_source'], $_GET['utm_secret'] );
	}

	/**
	 * The referrer's domain is its registrable one, and a referrer from the
	 * site itself is not external.
	 */
	public function test_the_referrer_domain_and_whether_it_is_external(): void {
		$_SERVER['HTTP_REFERER'] = 'https://news.bbc.co.uk/story';
		$this->assertSame( 'bbc.co.uk', Request::get_referrer_domain() );
		$this->assertTrue( Request::is_external_referrer() );
		$this->assertTrue( Request::has_referrer() );

		$_SERVER['HTTP_REFERER'] = 'https://www.google.com/search?q=x';
		$this->assertSame( 'google.com', Request::get_referrer_domain() );

		$_SERVER['HTTP_REFERER'] = 'https://shop.example/cart';
		$this->assertFalse( Request::is_external_referrer() );

		$_SERVER['HTTP_REFERER'] = 'javascript:alert(1)';
		$this->assertSame( '', Request::get_referrer() );
		$this->assertFalse( Request::has_referrer() );

		// network's reading: an address has no registrable domain, so two
		// LAN addresses are not each other's site.
		$this->assertSame( '192.168.1.10', Request::get_referrer_domain( [ 'referrer_url' => 'http://192.168.1.10/x' ] ) );
	}

	/**
	 * The agent is network's reading of it: trimmed and capped before any
	 * rule's pattern runs over it.
	 */
	public function test_the_user_agent_is_capped(): void {
		$_SERVER['HTTP_USER_AGENT'] = '  ' . str_repeat( 'A', 5000 ) . '  ';

		$this->assertSame( 512, strlen( Request::get_user_agent() ) );
		$this->assertSame( 'given', Request::get_user_agent( [ 'user_agent' => 'given' ] ) );

		unset( $_SERVER['HTTP_USER_AGENT'] );
	}

	/**
	 * Cookie and header values keep their percent-encoded octets.
	 *
	 * sanitize_text_field() deletes every %xx sequence, so a URL-encoded
	 * cookie -- which is most of them -- never equalled what the admin typed.
	 */
	public function test_encoded_values_survive(): void {
		$_COOKIE['tracking'] = 'abc%7Cdef%20x';
		$_SERVER['HTTP_X_PROMO'] = '100%25off';

		$this->assertSame( 'abc%7Cdef%20x', Request::get_cookie_value( [], 'tracking:whatever' ) );
		$this->assertSame( '100%25off', Request::get_header_value( [], 'X-Promo:whatever' ) );

		unset( $_COOKIE['tracking'] );
	}

	/**
	 * An Android WebView is a current Chrome, not an old Safari.
	 */
	public function test_a_webview_is_not_an_outdated_browser(): void {
		$webview = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/124.0.0.0 Mobile Safari/537.36';
		$old     = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1 Safari/605.1.15';

		$this->assertFalse( Request::is_user_agent_outdated_browser( [ 'user_agent' => $webview ] ) );
		$this->assertTrue( Request::is_user_agent_outdated_browser( [ 'user_agent' => $old ] ) );
		$this->assertFalse( Request::is_user_agent_headless( [ 'user_agent' => 'Slack/4.36 Electron/28.1' ] ), 'A desktop app is not a headless browser.' );
	}
}
