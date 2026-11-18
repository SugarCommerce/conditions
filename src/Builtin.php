<?php
/**
 * Built-in Conditions
 *
 * The conditions a set can name by id instead of configuring.
 *
 * @package     SugarCommerce\Conditions
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace SugarCommerce\Conditions;

/**
 * Class Builtin
 *
 * Every condition here needs WordPress and nothing else. Conditions about a
 * particular shop -- EDD's carts, WooCommerce's orders -- or a paid lookup
 * service are not built in: they are a configuration array or a Condition
 * class the plugin that knows about them registers (see the README).
 */
final class Builtin {

	/**
	 * The packs, in the order their conditions are listed.
	 *
	 * @var array<int, class-string>
	 */
	private const PACKS = [
		Builtin\DateTime::class,
		Builtin\User::class,
		Builtin\Post::class,
		Builtin\Request::class,
		Builtin\Context::class,
		Builtin\Email::class,
		Builtin\Geo::class,
		Builtin\Velocity::class,
		Builtin\Payment::class,
	];

	/**
	 * Get a built-in condition's configuration by id.
	 *
	 * @param string $name The condition id, e.g. `user_role`.
	 *
	 * @return array|null
	 */
	public static function get( string $name ): ?array {
		return self::get_all()[ $name ] ?? null;
	}

	/**
	 * Every built-in condition, by id.
	 *
	 * @return array<string, array>
	 */
	public static function get_all(): array {
		$all = [];

		foreach ( self::PACKS as $pack ) {
			$all = array_merge( $all, $pack::get_all() );
		}

		return $all;
	}
}
