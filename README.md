# Conditions

Let a shop owner build a rule — "logged-in customers in the UK, on a
weekend" — in the WordPress admin, and ask in code whether it matches.

## What it does

Anything with rules ends up needing this: a discount that applies to some
carts, a fee for some countries, an order held for review when it looks like
fraud. Hard-coding them means a release every time one changes; a settings
field means parsing whatever the owner typed.

This gives the owner a builder — pick a condition, an operator and a value,
and group them with any/all — and gives you one call that answers.
Conditions about your shop, your customers or a paid lookup service are
yours to add.

It leans on two SugarCommerce packages rather than carrying copies:
sugarcommerce/network reads the patterns in an IP rule (its `Range`: an
address, `/24`, `203.0.113.*`, `2001:db8:*`, or `a-b`), and
sugarcommerce/plugin's `Assets` finds where the builder's scripts are
served from and registers its build. Both are Strauss-safe, so any plugin
can still carry it.

## Features

* Register a set of rules with its own admin screen, from one call
* Give owners conditions on users, posts, dates, the request, email addresses and countries
* Add a condition of your own, with its own operators and values
* Pick posts, terms and users inside a rule by searching, rather than typing ids
* Check a set against whatever context you have, and ask which rule matched and why
* Judge rules you keep in your own table or option with the same engine
* Catch card-testing and fraud rings with velocity rules answered by your own counts

## Installation

```bash
composer require sugarcommerce/conditions
```

## Quick start

Register the set, and the screen that edits it:

```php
use SugarCommerce\Conditions\Conditions;

add_action( 'init', function () {
	Conditions::use_prefix( 'myshop' );

	Conditions::register( 'discount_rule', [
		'labels'     => [ 'singular' => 'Discount Rule', 'plural' => 'Discount Rules' ],
		'menu_icon'  => 'dashicons-tag',
		'capability' => 'manage_shop_settings',
		'conditions' => [
			'user_role',
			'day_of_week',
			'cart_total' => [ 'label' => 'Cart Total', 'group' => 'Cart', 'type' => 'number', 'arg' => 'cart_total' ],
		],
	] );
} );
```

Then, where the rule matters:

```php
$match = Conditions::check( 'discount_rule', [ 'user_id' => get_current_user_id(), 'cart_total' => 120.00 ] );

if ( $match->matched() ) {
	$percent = (int) $match->get_rule_meta( '_percent' );   // the rule is a post: keep its settings as meta
}
```

## A set of rules

A set is a private post type named after its id: each rule is a post, its
groups saved in `_conditions` meta. Groups are ORed, the rules inside a group
ANDed. The post type answers to the set's `capability` for everything —
editing, publishing, trashing — not only for the conditions.

| Argument | Default | |
|---|---|---|
| `labels` | from the id | `singular`, `plural` |
| `conditions` | `[]` | built-in ids, `id => configuration`, or Condition classes |
| `capability` | `manage_options` | |
| `menu_icon`, `menu_parent`, `show_in_menu`, `show_ui` | top-level menu | `show_in_menu => false` keeps the edit screens without a menu item |
| `parent_file`, `submenu_file` | — | which menu to highlight while editing |
| `supports` | `title`, `page-attributes` | the Order box sets the order rules are checked in |
| `description`, `metabox_title` | — | the builder's text |
| `redirect` | — | `after_trash` admin path, `show_undo` |

`Conditions::add( 'discount_rule', 'is_weekend' )` adds a condition later —
to your set or to one another plugin registered.

## Conditions of your own

A configuration array:

```php
'cart_total' => [
	'label'         => 'Cart Total',
	'group'         => 'Cart',
	'type'          => 'number',                               // the field and its operators
	'compare_value' => fn( array $args ) => edd_get_cart_total(),
	'required_args' => [],                                     // missing args skip the rule, never pass it
],
```

The types are `text`, `number`, `number_unit`, `text_unit`, `boolean`,
`date`, `time`, `select`, `tags`, `ip`, `email`, and the searched pickers
`post` (with `post_type`), `term` (with `taxonomy`), `user` (with `role`)
and `ajax` (with an `ajax => fn( $search, $ids )` callback). `multiple`,
`options`, `units`, `operators`, `placeholder`, `description`, `min`, `max`,
`step`, `sanitize` and `compare_as` refine them.

Or a class, for comparison logic of its own:

```php
use SugarCommerce\Conditions\Condition;

final class Lifetime_Value extends Condition {
	protected string $name  = 'lifetime_value';
	protected string $label = 'Lifetime Value';
	protected string $type  = 'number';

	public function get_compare_value( array $args, mixed $user_value = null ): mixed {
		return edd_get_customer_by( 'email', $args['email'] ?? '' )?->purchase_value;
	}
}
```

## Checking

```php
Conditions::check( $set, $args );        // MatchResult: the first rule, in Order
Conditions::check_all( $set, $args );    // MatchResults: every rule that matched
```

A `MatchResult` has `matched()`, `get_rule()`, `get_rule_id()`,
`get_rule_title()`, `get_rule_meta()` and `get_matched_group()` — the group
that fired, for a log that says why. `MatchResults` counts, iterates,
`filter()`s and has `get_rule_ids()`. A third argument narrows which rules
are read with WP_Query arguments (`meta_query`, `posts_per_page`); the post
type is always the set's.

A rule nobody finished — no groups, or no rule in a group that could be
judged — matches nothing. A missing argument is not a zero, and a lookup
that failed is not a "no".

Rules kept somewhere else — a term, an option, your own table — are judged
the same way:

```php
Conditions::matches( 'discount_rule', $groups, $args );   // bool
( new Matcher( 'discount_rule', $args ) )->first_matching_group( $groups );
```

## What the library cannot know

It reads no forwarding headers and carries no country names. Hand it what it
cannot know:

```php
use SugarCommerce\Network\ClientIp;
use SugarCommerce\Network\Country;
use SugarCommerce\Network\TrustedProxies;

Conditions::provide( 'ip', fn() => ClientIp::resolve( null, TrustedProxies::cloudflare() ) );  // else REMOTE_ADDR
Conditions::provide( 'country', fn() => Country::resolve() );                                   // else the `country` argument only
Conditions::provide( 'countries', fn() => SugarCommerce\Geo\Countries::all() );                 // else ext-intl's names
```

## Velocity

The velocity conditions (`velocity_orders_by_ip`, `…_by_email`,
`…_by_card_fingerprint`, failed, blocked, same-product, distinct emails and
IPs) count nothing themselves: the plugin that keeps the orders answers,
with the rule's own window.

```php
use SugarCommerce\Conditions\Helpers\Velocity;

add_filter( 'myshop_conditions_velocity_orders_by_ip', function ( $count, $ip, $number, $unit ) {
	global $wpdb;

	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}edd_orders WHERE ip = %s AND date_created >= DATE_SUB( UTC_TIMESTAMP(), " . Velocity::to_interval( $number, $unit ) . ' )',
		$ip
	) );
}, 10, 4 );
```

With no answer the rule is not judged. A count worked out beforehand can be
passed as `velocity_orders_by_ip`; `Velocity::compute_card_fingerprint()`
makes the salted card hash the card counts key on.

## Visitor conditions from sugarcommerce/network

Telling a browser, a bot or a traffic source apart needs tables this library
does not carry. With sugarcommerce/network, register the ones you want:

```php
use SugarCommerce\Network\Referrer;
use SugarCommerce\Network\Request;
use SugarCommerce\Network\UserAgent;

$any_none = SugarCommerce\Conditions\Operators::collection_any_none();
$agent    = fn() => Request::agent();
$options  = fn( array $names ) => array_map( fn( $n ) => [ 'value' => $n, 'label' => $n ], $names );

Conditions::add( $set, 'device_type', [ 'type' => 'select', 'multiple' => true, 'operators' => $any_none, 'options' => $options( [ 'mobile', 'tablet', 'desktop', 'bot' ] ), 'compare_value' => fn( $a ) => $a['device_type'] ?? Request::device_type() ] );
Conditions::add( $set, 'browser', [ 'type' => 'select', 'multiple' => true, 'operators' => $any_none, 'options' => fn() => $options( UserAgent::browsers() ), 'compare_value' => fn( $a ) => $a['browser'] ?? UserAgent::browser( $agent() ) ] );
Conditions::add( $set, 'operating_system', [ 'type' => 'select', 'multiple' => true, 'operators' => $any_none, 'options' => fn() => $options( UserAgent::operating_systems() ), 'compare_value' => fn( $a ) => UserAgent::os( $agent() ) ] );
Conditions::add( $set, 'in_app_browser', [ 'type' => 'select', 'multiple' => true, 'operators' => $any_none, 'options' => fn() => $options( UserAgent::in_app_browsers() ), 'compare_value' => fn( $a ) => Request::in_app_browser() ] );
Conditions::add( $set, 'is_bot', [ 'type' => 'boolean', 'compare_value' => fn( $a ) => UserAgent::is_bot( $agent() ) ] );
Conditions::add( $set, 'is_ai_bot', [ 'type' => 'boolean', 'compare_value' => fn( $a ) => Request::is_ai_bot() ] );
Conditions::add( $set, 'is_ai_user_agent', [ 'type' => 'boolean', 'compare_value' => fn( $a ) => Request::is_ai_user_agent() ] );
Conditions::add( $set, 'is_link_preview', [ 'type' => 'boolean', 'compare_value' => fn( $a ) => Request::is_link_preview() ] );
Conditions::add( $set, 'traffic_source', [ 'type' => 'select', 'multiple' => true, 'operators' => $any_none, 'options' => fn() => Referrer::get_traffic_source_options( true ), 'compare_value' => fn( $a ) => Referrer::get_traffic_source() ] );
Conditions::add( $set, 'search_engine', [ 'type' => 'select', 'multiple' => true, 'operators' => $any_none, 'options' => fn() => Referrer::get_search_engine_options( true ), 'compare_value' => fn( $a ) => Referrer::get_search_engine() ] );
Conditions::add( $set, 'social_platform', [ 'type' => 'select', 'multiple' => true, 'operators' => $any_none, 'options' => fn() => Referrer::get_social_platform_options( true ), 'compare_value' => fn( $a ) => Referrer::get_social_platform() ] );
Conditions::add( $set, 'search_terms', [ 'type' => 'text', 'compare_value' => fn( $a ) => Referrer::get_search_terms() ] );
```

The same ids as before, so rules saved against them keep working. A lookup
service — ProxyCheck, IPQualityScore, AbuseIPDB — is one more of these, its
`compare_value` asking the service's client about `$args['ip']`.

## The scripts

The rule builder is plain JavaScript in ES modules (`assets/js`), each with one
job, built by `@wordpress/scripts` into `assets/build/conditions.js` with a
`conditions.asset.php` naming the WordPress scripts it imports
(`wp-api-fetch`, `wp-dom-ready`, `wp-i18n`) and a version that changes with
its contents. `Admin\Assets` registers it through
`SugarCommerce\Plugin\Assets::register_built_script()` as
`{prefix}-conditions-admin`, after `{prefix}-conditions-select2`, on a set's
edit screens only; `…_assets_url` still moves it with the stylesheets.

| Module | Its job |
|---|---|
| `builder/builder` | starts the builder: its buttons, its condition selects, the saved groups |
| `builder/rows` | adding, copying and deleting groups; adding and removing rows |
| `builder/rule` | a row following its condition: operators, value field, tooltip |
| `builder/operators` | which operators a condition offers -- `Operators::for_type()`'s answer |
| `builder/fields` | the value field for each type, and the names the save reads |
| `builder/tags` | the free-entry fields: what splits entries and what is accepted (tags, IP/CIDR, email patterns) |
| `builder/search` | the post, term, user and `ajax` pickers' REST requests, and labelling saved values |
| `builder/selects` | which picker each select becomes |
| `builder/serialize` | a row read back as a saved rule, for Duplicate |
| `builder/templates` | a group, a row and an OR copied from the `<template>`s `Admin\Editor` draws |
| `lib/select2` | the one place Select2 -- and through it jQuery -- is reached |
| `lib/config`, `lib/dom` | what PHP handed over (`window.sugarConditions[ $set ]`); escaping |

Select2 stays: it is bundled, loaded by its own handle, and it brings jQuery.
Only `lib/select2` touches it, reading `window.jQuery` when it is wanted --
without it every select is a plain one and the builder still works -- so
another picker can replace it by replacing that file. The strings a plugin
may change through `…_i18n_strings` come from PHP; the few placeholders the
script writes itself are `__()` in the `sugarcommerce` text domain.

```bash
npm ci
npm run build      # assets/build, committed
npm run start      # rebuild on change
npm run test:js    # Vitest, in jsdom, against the markup PHP draws
npm run lint:js
```

The build's files are what ship. CI builds again and fails when the result
differs from what was committed.

## Two plugins on one site

Each plugin bundles its own copy, and the REST routes, hooks and script
handles are global. `Conditions::use_prefix( 'myshop' )`, before the first
`register()`, gives this copy its own: routes under `myshop-conditions/v1`,
hooks named `myshop_conditions_*`, handles `myshop-conditions-*`.

| Hook | |
|---|---|
| `{prefix}_conditions_set_registered` | action, after a set registers |
| `{prefix}_conditions_velocity_{kind}` | filter, a velocity count |
| `{prefix}_conditions_freemail_domains` | filter, the free email providers |
| `{prefix}_conditions_rest_permission` | filter, who may search |
| `{prefix}_conditions_localized_data`, `…_i18n_strings`, `…_assets_url` | filters, the builder |
| `{set}_save_conditions`, `{set}_conditions_saved` | filter and action, saving a rule |
| `{set}_labels`, `{set}_metabox_title`, `{set}_post_updated_messages` | filters, the screens |

## Security

* Editing rules you may not: a set's rules are saved only with its nonce and
  its capability, and its pickers' routes ask a capability of a set whose
  editor could have asked (`{prefix}_conditions_rest_permission` has the
  last word).
* Searching more than a picker offers: post, term and user pickers search
  only the post types, taxonomies and roles a registered condition names;
  leaving the role out searches those roles, not every account.
* Learning who is on the site: a user picker matches email addresses only
  for someone who may `list_users`, as core's users route does; it answers
  with display names, never addresses.
* Unpublished titles: the post picker lists published posts only.
* A spoofed visitor address: the IP is `REMOTE_ADDR` unless a provider that
  knows the site's proxies says otherwise, and a CDN's country header is not
  read on its own.
* A broken pattern from a rule: a regex that will not compile is refused when
  it is saved and is "no match" when checked, never a warning on a customer's
  page.
* Leaking internals: a picker callback that throws answers that the search
  could not be completed; its message only under `WP_DEBUG`.

## Source layout

* `src/Conditions.php` (configuration and hooks), `Registry.php` (sets and
  conditions, with `src/Traits/Registry/Registration.php`), `Matcher.php`,
  `Comparator.php`, `Operators.php`, `Condition.php`, `MatchResult(s).php`.
* `src/Builtin/` (the conditions that ship), `src/Helpers/` (what they read:
  the request -- with `src/Traits/Helpers/Request/` for the referrer and the
  user agent -- the user, the post, dates, email, geo, velocity),
  `src/Options/` (choices for the builder's fields).
* `src/Admin/` (the rule post type, its meta box, the builder, sanitizing),
  `src/Rest/` (the pickers' routes).
* `src/` is PHP only. The builder's sources are in `assets/js/`, built to
  `assets/build/`; Select2 ships beside them as `assets/js/select2.min.js`;
  the styles are in `assets/css/`.

## Requirements

* PHP 8.3 or later
* WordPress 7.1 or later
* sugarcommerce/network and sugarcommerce/plugin

## License

GPL-2.0-or-later
