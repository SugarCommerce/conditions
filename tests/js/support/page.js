/**
 * The builder's page as PHP draws it, Select2 on it or not, and the library's
 * REST routes as a fake.
 *
 * `editorPage()` runs `editor.php` beside this file, so the tests use the
 * markup Admin\Editor renders and the data Admin\Assets hands the script --
 * not a copy of either that could drift.
 */

import { execFileSync } from 'node:child_process';
import { mkdtempSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import jQuery from 'jquery';

const here = dirname( fileURLToPath( import.meta.url ) );
const select2Source = readFileSync(
	join( here, '../../../assets/js/select2.min.js' ),
	'utf8'
);

/**
 * Draw the meta box into the document and hand the script its data.
 *
 * @param {Array} saved The groups the rule was saved with.
 * @return {Object} The set's localized data, as window.sugarConditions holds it.
 */
export function editorPage( saved = [] ) {
	const dir = mkdtempSync( join( tmpdir(), 'conditions-' ) );
	const file = join( dir, 'saved.json' );

	writeFileSync( file, JSON.stringify( saved ) );

	const { html, config } = JSON.parse(
		execFileSync( 'php', [ join( here, 'editor.php' ), file ], {
			encoding: 'utf8',
		} )
	);

	document.body.innerHTML = `<form id="post">${ html }</form>`;
	window.sugarConditions = { discount_rule: config };

	return config;
}

/**
 * Put jQuery and the bundled Select2 on the page, as PHP's handles do.
 *
 * @return {Function} jQuery.
 */
export function withSelect2() {
	window.jQuery = jQuery;
	globalThis.jQuery = jQuery;

	if ( ! jQuery.fn.select2 ) {
		// The bundled file, as the browser runs it: a UMD that finds the
		// global jQuery.
		new Function( select2Source )();
	}

	return jQuery;
}

/**
 * Take jQuery off the page: the builder must work with plain selects.
 */
export function withoutJQuery() {
	delete window.jQuery;
	delete globalThis.jQuery;
}

/**
 * The library's search routes, answering from fixed data, and every request
 * kept for the test to read.
 *
 * @param {Object} answers Path (`posts`, `terms`, `users`, `ajax`) => ( params ) => items.
 * @return {Function} An apiFetch stand-in, with `.calls`.
 */
export function fakeRoutes( answers = {} ) {
	const calls = [];

	const fetcher = ( options ) => {
		calls.push( options );

		const url = new URL( options.url );
		const route = url.pathname.split( '/' ).pop();
		const answer = answers[ route ];

		if ( options.signal && options.signal.aborted ) {
			return Promise.reject( new Error( 'aborted' ) );
		}

		if ( ! answer ) {
			return Promise.reject( { code: 'rest_no_route' } );
		}

		return Promise.resolve(
			answer( Object.fromEntries( url.searchParams.entries() ) )
		);
	};

	fetcher.calls = calls;

	return fetcher;
}

/**
 * Let pending promises and Select2's timers run.
 *
 * @param {number} ms How long.
 * @return {Promise<void>} Settles after.
 */
export const tick = ( ms = 0 ) =>
	new Promise( ( resolve ) => setTimeout( resolve, ms ) );

/**
 * What the form would post for the rules, in order, ids normalised to their
 * position: `_conditions[g0][rules][r0][value][]` and so on.
 *
 * @return {Array<[string, string]>} Name/value pairs.
 */
export function posted() {
	const ids = new Map();
	const short = ( id, prefix ) => {
		if ( ! ids.has( id ) ) {
			ids.set(
				id,
				prefix +
					[ ...ids.keys() ].filter( ( key ) =>
						ids.get( key ).startsWith( prefix )
					).length
			);
		}

		return ids.get( id );
	};

	return [ ...new window.FormData( document.getElementById( 'post' ) ) ]
		.filter( ( [ name ] ) => name.startsWith( '_conditions[' ) )
		.map( ( [ name, value ] ) => [
			name.replace(
				/^_conditions\[([^\]]+)\]\[rules\]\[([^\]]+)\]/,
				( all, group, rule ) =>
					`_conditions[${ short( group, 'g' ) }][rules][${ short(
						rule,
						'r'
					) }]`
			),
			value,
		] );
}
