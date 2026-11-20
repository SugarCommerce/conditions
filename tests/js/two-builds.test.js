/**
 * Two plugins' copies of the library on one screen: each starts its own
 * builder alone, from its own entry of the registry and its own templates.
 *
 * The built script itself is run, twice, as WordPress prints it under two
 * handles -- its <script> element's id is what tells it which copy it is.
 * Both sets are called `discount_rule`: a set's id is the plugin's choice.
 */

import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

import { JSDOM } from 'jsdom';
import { expect, test } from 'vitest';

const BUILT = readFileSync(
	resolve( __dirname, '../../assets/build/conditions.js' ),
	'utf8'
);

// The meta box as Admin\Editor draws it, and the data Admin\Assets hands
// over, for the build `shop-conditions-admin`.
const { html, build, config } = JSON.parse(
	execFileSync( 'php', [ resolve( __dirname, 'support/editor.php' ) ], {
		encoding: 'utf8',
	} )
);

const OTHER = 'other-conditions-admin';

/**
 * The inline data and the script, as WordPress prints them for a handle.
 *
 * @param {string} handle The copy's handle.
 * @param {Object} data   The set's data.
 * @return {string} Markup.
 */
const printed = ( handle, data ) => {
	const key = JSON.stringify( handle );

	return (
		`<script>window.SugarCommerceConditions=window.SugarCommerceConditions||{};window.SugarCommerceConditions[${ key }]=window.SugarCommerceConditions[${ key }]||{};window.SugarCommerceConditions[${ key }]["discount_rule"]=${ JSON.stringify(
			data
		) };</script>` +
		`<script id="${ handle }-js">${ BUILT.replace(
			/<\/script/gi,
			'<\\/script'
		) }</script>`
	);
};

test( 'the built script, loaded by two copies, starts each copy’s builder once, from its own templates', () => {
	// The other plugin's meta box: its own build, and a condition the
	// first plugin's set does not have.
	const theirs = html
		.replace( `data-build="${ build }"`, `data-build="${ OTHER }"` )
		.replace(
			'<select class="condition-select conditions-condition-select">',
			'<select class="condition-select conditions-condition-select"><option value="theirs">Theirs</option>'
		);

	expect( theirs ).toContain( `data-build="${ OTHER }"` );
	expect( theirs ).toContain( 'value="theirs"' );

	const dom = new JSDOM(
		'<!doctype html><html><head><script>window.wp={apiFetch:function(){return new Promise(function(){});},domReady:function(f){f();},i18n:{__:function(s){return s;},_x:function(s){return s;},_n:function(s,p,n){return n===1?s:p;},sprintf:function(s){return s;}}};</script></head>' +
			`<body><div id="mine">${ html }</div><div id="theirs">${ theirs }</div>` +
			printed( build, config ) +
			printed( OTHER, {
				...config,
				restUrl: 'https://other.test/wp-json/other-conditions/v1',
			} ) +
			'</body></html>',
		{ runScripts: 'dangerously' }
	);

	const { document } = dom.window;
	const groups = ( id ) =>
		document.querySelectorAll( `#${ id } .condition-group` ).length;
	const offered = ( id ) =>
		[
			...document.querySelector(
				`#${ id } .condition-groups .condition-select`
			).options,
		].map( ( option ) => option.value );

	// Once each: two copies that each started every builder would give the
	// first two groups and the second none.
	expect( groups( 'mine' ) ).toBe( 1 );
	expect( groups( 'theirs' ) ).toBe( 1 );

	expect( offered( 'theirs' ) ).toContain( 'theirs' );
	expect( offered( 'mine' ) ).not.toContain( 'theirs' );

	expect( Object.keys( dom.window.SugarCommerceConditions ) ).toEqual( [
		build,
		OTHER,
	] );
	expect( dom.window.sugarConditions ).toBeUndefined();

	dom.window.close();
} );
