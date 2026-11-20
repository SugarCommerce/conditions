<?php
/**
 * Conditions
 *
 * The one place a plugin talks to: register a set of rules with its own
 * admin screen, add conditions to it, and ask whether the rules match.
 *
 * @package     SugarCommerce\Conditions
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions;

use SugarCommerce\Conditions\Models\MatchResult;
use SugarCommerce\Conditions\Models\MatchResults;
use SugarCommerce\Plugin\Build;

/**
 * Class Conditions
 *
 * Static entry point. Replaces the old global helpers -- register_conditions(),
 * register_condition(), check_conditions(), check_all_conditions() -- which
 * were the one thing Strauss cannot prefix: two plugins bundling different
 * copies shared whichever copy declared them first.
 */
final class Conditions {

	/**
	 * How many segments this class's own namespace has --
	 * SugarCommerce\Conditions -- before any prefix. Counted rather than
	 * written out: Strauss rewrites a namespace it finds in a string.
	 *
	 * @var int
	 */
	private const int OWN_SEGMENTS = 2;

	/**
	 * The prefix the plugin chose, in place of the derived one.
	 *
	 * @var string|null
	 */
	private static ?string $prefix = null;

	/**
	 * Sources a plugin has handed in for what the library cannot know alone.
	 *
	 * @var array<string, callable>
	 */
	private static array $providers = [];

	/**
	 * Name everything this copy shares with WordPress after a prefix.
	 *
	 * The REST namespace, the action and filter names, and the script and
	 * style handles are global. A plugin bundles its own copy of this library,
	 * and two copies on one site answering on the same REST route meant one of
	 * them searched for posts with the other's registry -- so the second
	 * plugin's post pickers came back "not allowed". With a prefix each copy
	 * has its own:
	 *
	 *  - hooks:  `{prefix}_conditions_{name}` (`conditions_{name}` with none);
	 *  - REST:   `{prefix}-conditions/v1` (`conditions/v1` with none);
	 *  - assets: `{prefix}-conditions-admin` (`conditions-admin` with none).
	 *
	 * Without it a Strauss-prefixed copy takes its prefix from the namespace
	 * it was compiled under ({@see self::prefix_for()}), so two copies
	 * neither of which named itself still come out apart; a plain Composer
	 * install has none.
	 *
	 * Call it before the first register().
	 *
	 * @param string $prefix e.g. `myplugin`. Anything but letters, digits and
	 *                       underscores is dropped. Empty to go back to the
	 *                       derived prefix.
	 *
	 * @return void
	 */
	public static function use_prefix( string $prefix ): void {
		$prefix       = strtolower( (string) preg_replace( '/[^A-Za-z0-9_]/', '', $prefix ) );
		self::$prefix = '' === $prefix ? null : $prefix;
	}

	/**
	 * The prefix in use: the one chosen, else this build's own, '' for none.
	 *
	 * @return string
	 */
	public static function prefix(): string {
		return self::$prefix ?? self::prefix_for( __NAMESPACE__ );
	}

	/**
	 * The prefix a build compiled under a namespace has, when none is chosen.
	 *
	 * {@see Build::name()}, the family's one rule: '' for this package's own
	 * namespace, `myplugin` for "MyPlugin\Vendor\SugarCommerce\Conditions".
	 * Split out so a test can reach the prefixed case.
	 *
	 * @param string $under The namespace this class is compiled under.
	 *
	 * @return string
	 */
	public static function prefix_for( string $under ): string {
		return str_replace( '-', '_', Build::name( $under, self::OWN_SEGMENTS ) );
	}

	/**
	 * The name of one of this copy's actions or filters.
	 *
	 * @param string $name e.g. `set_registered`.
	 *
	 * @return string e.g. `myplugin_conditions_set_registered`.
	 */
	public static function hook( string $name ): string {
		$prefix = self::prefix();

		return ( '' === $prefix ? '' : $prefix . '_' ) . 'conditions_' . $name;
	}

	/**
	 * This copy's REST namespace.
	 *
	 * @return string e.g. `myplugin-conditions/v1`.
	 */
	public static function rest_namespace(): string {
		return self::slug() . '/v1';
	}

	/**
	 * The handle of one of this copy's scripts or styles.
	 *
	 * @param string $name e.g. `admin`.
	 *
	 * @return string e.g. `myplugin-conditions-admin`.
	 */
	public static function handle( string $name ): string {
		return self::slug() . '-' . $name;
	}

	/**
	 * The prefix and the library's name, joined for a URL or a handle.
	 *
	 * @return string
	 */
	private static function slug(): string {
		$prefix = self::prefix();

		return ( '' === $prefix ? '' : str_replace( '_', '-', $prefix ) . '-' ) . 'conditions';
	}

	/** Sets **********************************************************************/

	/**
	 * Register a set of rules, the post type that stores them and its screen.
	 *
	 * ```php
	 * Conditions::register( 'discount_rule', [
	 *     'labels'     => [ 'singular' => 'Discount Rule', 'plural' => 'Discount Rules' ],
	 *     'menu_icon'  => 'dashicons-tag',
	 *     'conditions' => [
	 *         'user_role',                                  // built in
	 *         'cart_total' => [ 'type' => 'number', 'arg' => 'cart_total' ],
	 *         My_Condition::class,                          // a class
	 *     ],
	 * ] );
	 * ```
	 *
	 * @param string $set_id The set's id, which is also its post type.
	 * @param array  $args   See {@see Registry::register_set()}.
	 *
	 * @return void
	 */
	public static function register( string $set_id, array $args = [] ): void {
		Registry::register_set( $set_id, $args );
	}

	/**
	 * Add a condition to a set -- yours, or one another plugin registered.
	 *
	 * @param string $set_id    The set.
	 * @param string $condition A built-in id, a Condition class, or a new id.
	 * @param array  $args      The configuration, for a new id.
	 *
	 * @return void
	 */
	public static function add( string $set_id, string $condition, array $args = [] ): void {
		Registry::register_condition( $set_id, $condition, $args );
	}

	/** Checking ******************************************************************/

	/**
	 * The first rule in a set that matches.
	 *
	 * Rules are read published, in their Order, oldest first on a tie.
	 *
	 * @param string $set_id     The set.
	 * @param array  $args       What the rules are judged against.
	 * @param array  $query_args WP_Query arguments for which rules to read;
	 *                           the post type is always the set's.
	 *
	 * @return MatchResult
	 */
	public static function check( string $set_id, array $args = [], array $query_args = [] ): MatchResult {
		return ( new Matcher( $set_id, $args, $query_args ) )->check();
	}

	/**
	 * Every rule in a set that matches.
	 *
	 * @param string $set_id     The set.
	 * @param array  $args       What the rules are judged against.
	 * @param array  $query_args WP_Query arguments for which rules to read.
	 *
	 * @return MatchResults
	 */
	public static function check_all( string $set_id, array $args = [], array $query_args = [] ): MatchResults {
		return ( new Matcher( $set_id, $args, $query_args ) )->check_all();
	}

	/**
	 * Whether groups of rules kept somewhere else match.
	 *
	 * For rules stored in a table, an option or a term instead of a rule
	 * post: the same groups the editor saves, judged with the set's
	 * conditions.
	 *
	 * @param string $set_id The set whose conditions the rules use.
	 * @param array  $groups Groups of rules, as the editor saves them.
	 * @param array  $args   What the rules are judged against.
	 *
	 * @return bool
	 */
	public static function matches( string $set_id, array $groups, array $args = [] ): bool {
		return ( new Matcher( $set_id, $args ) )->matches( $groups );
	}

	/** Providers *****************************************************************/

	/**
	 * Hand the library something it cannot know on its own.
	 *
	 * The library depends on nothing, so it does not know a visitor's real IP
	 * behind a proxy, their country, or the names of countries. Each is a
	 * callable a plugin can supply:
	 *
	 *  - `ip`        -- `fn(): ?string`, the visitor's IP. Without it, REMOTE_ADDR.
	 *  - `country`   -- `fn(): ?string`, the visitor's two-letter country.
	 *                   Without it, only a `country` argument answers.
	 *  - `countries` -- `fn(): array<string, string>`, code => name, for the
	 *                   country pickers. Without it, ext-intl's names, or the
	 *                   codes themselves.
	 *
	 * ```php
	 * Conditions::provide( 'ip', fn() => ClientIp::resolve( null, TrustedProxies::cloudflare() ) );
	 * ```
	 *
	 * @param string   $name   One of the names above.
	 * @param callable $source The callable.
	 *
	 * @return void
	 */
	public static function provide( string $name, callable $source ): void {
		self::$providers[ $name ] = $source;
	}

	/**
	 * Whether a plugin has provided something.
	 *
	 * @param string $name The provider's name.
	 *
	 * @return bool
	 */
	public static function has_provider( string $name ): bool {
		return isset( self::$providers[ $name ] );
	}

	/**
	 * Ask a provider, or get the fallback when there is none.
	 *
	 * @param string $name     The provider's name.
	 * @param mixed  $fallback What to answer without one.
	 *
	 * @return mixed
	 */
	public static function provided( string $name, mixed $fallback = null ): mixed {
		return isset( self::$providers[ $name ] ) ? call_user_func( self::$providers[ $name ] ) : $fallback;
	}

	/**
	 * Forget every set, condition, provider and the prefix (for tests).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$prefix    = null;
		self::$providers = [];
		Registry::reset();
	}
}
