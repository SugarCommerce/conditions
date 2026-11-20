/**
 * The searched fields' requests: the URLs the REST routes are asked (the
 * same parameters, in the same order, the jQuery builder sent), and labelling
 * a saved rule's values.
 */

import { describe, expect, test, vi } from 'vitest';

import {
	label,
	labelUrl,
	queryString,
	request,
	search,
	searchUrl,
	sourceOf,
	toResults,
} from '../../src/js/builder/search';

const REST = 'https://shop.example/wp-json/shop-conditions/v1';

/**
 * A value select as fields.js draws it.
 *
 * @param {string} markup The select's markup.
 * @return {HTMLSelectElement} The select.
 */
function select( markup ) {
	const wrapper = document.createElement( 'div' );

	wrapper.innerHTML = markup;

	return wrapper.firstElementChild;
}

const post = () =>
	select(
		'<select class="value-select conditions-ajax-select multiple" multiple data-type="post" data-post-type="page"><option value="4" selected>4</option><option value="9" selected>9</option></select>'
	);

const custom = () =>
	select(
		'<select class="value-select conditions-custom-ajax-select" data-type="ajax" data-set-id="discount_rule" data-condition-id="customer"><option value="a@b.c" selected>a@b.c</option></select>'
	);

test( 'a query string sends empty values and leaves out missing ones', () => {
	expect(
		queryString( {
			search: '',
			post_type: 'page',
			role: undefined,
			x: null,
		} )
	).toBe( '?search=&post_type=page' );
	expect( queryString( { include: 'a@b.c,4 5' } ) ).toBe(
		'?include=a%40b.c%2C4%205'
	);
	expect( queryString( {} ) ).toBe( '' );
	expect( queryString( { role: undefined } ) ).toBe( '' );
} );

describe( 'the routes asked', () => {
	test.each( [
		[
			'post',
			'data-post-type="page"',
			`${ REST }/posts?search=sh&post_type=page`,
			`${ REST }/posts?post_type=page`,
			`${ REST }/posts?include=4%2C9&post_type=page`,
		],
		[
			'term',
			'data-taxonomy="category"',
			`${ REST }/terms?search=sh&taxonomy=category`,
			`${ REST }/terms?taxonomy=category`,
			`${ REST }/terms?include=4%2C9&taxonomy=category`,
		],
		[
			'user',
			'data-role="administrator,editor"',
			`${ REST }/users?search=sh&role=administrator%2Ceditor`,
			`${ REST }/users?role=administrator%2Ceditor`,
			`${ REST }/users?include=4%2C9&role=administrator%2Ceditor`,
		],
		[
			'user',
			'',
			`${ REST }/users?search=sh`,
			`${ REST }/users`,
			`${ REST }/users?include=4%2C9`,
		],
	] )( '%s %s', ( type, attributes, typed, opened, labelled ) => {
		const source = sourceOf(
			select(
				`<select class="value-select conditions-ajax-select" data-type="${ type }" ${ attributes }></select>`
			),
			REST
		);

		expect( searchUrl( source, 'sh' ) ).toBe( typed );
		// Opened, before anything is typed: no search at all.
		expect( searchUrl( source, undefined ) ).toBe( opened );
		expect( labelUrl( source, [ '4', '9' ] ) ).toBe( labelled );
	} );

	test( 'only the parameter its type takes is searched with', () => {
		const source = sourceOf(
			select(
				'<select class="conditions-ajax-select" data-type="post" data-post-type="page" data-taxonomy="category" data-role="x"></select>'
			),
			REST
		);

		expect( searchUrl( source, '' ) ).toBe(
			`${ REST }/posts?search=&post_type=page`
		);
		// Labelling sends every one the select has.
		expect( labelUrl( source, [ '1' ] ) ).toBe(
			`${ REST }/posts?include=1&post_type=page&taxonomy=category&role=x`
		);
	} );

	test( 'a term or user picker never searches with a post type', () => {
		const term = sourceOf(
			select(
				'<select class="conditions-ajax-select" data-type="term" data-post-type="page" data-taxonomy="category"></select>'
			),
			REST
		);
		const user = sourceOf(
			select(
				'<select class="conditions-ajax-select" data-type="user" data-post-type="page" data-taxonomy="category" data-role="editor"></select>'
			),
			REST
		);

		expect( searchUrl( term, 'a' ) ).toBe(
			`${ REST }/terms?search=a&taxonomy=category`
		);
		expect( searchUrl( user, 'a' ) ).toBe(
			`${ REST }/users?search=a&role=editor`
		);
	} );

	test( "a condition's own search: its set and id first", () => {
		const source = sourceOf( custom(), REST );

		expect( searchUrl( source, 'ann' ) ).toBe(
			`${ REST }/ajax?set_id=discount_rule&condition_id=customer&search=ann`
		);
		expect( searchUrl( source, undefined ) ).toBe(
			`${ REST }/ajax?set_id=discount_rule&condition_id=customer`
		);
		expect( labelUrl( source, [ 'a@b.c', 'd@e.f' ] ) ).toBe(
			`${ REST }/ajax?set_id=discount_rule&condition_id=customer&include=a%40b.c%2Cd%40e.f`
		);
	} );

	test( 'a select that does not search has no route', () => {
		expect(
			sourceOf(
				select( '<select class="conditions-select2"></select>' ),
				REST
			)
		).toBeNull();
	} );
} );

test( "the routes' items become the select's results", () => {
	expect(
		toResults( [
			{ value: 4, label: 'Home' },
			{ value: 'x', label: '<b>' },
		] )
	).toEqual( [
		{ id: 4, text: 'Home' },
		{ id: 'x', text: '<b>' },
	] );
	expect( toResults( { code: 'oops' } ) ).toEqual( [] );
} );

test( 'a request carries the REST nonce, and a signal when it can be cancelled', () => {
	const fetcher = vi.fn( () => Promise.resolve( [] ) );
	const signal = new AbortController().signal;

	request( fetcher, 'u', 'abc' );
	request( fetcher, 'u', '', signal );

	expect( fetcher.mock.calls[ 0 ][ 0 ] ).toEqual( {
		url: 'u',
		method: 'GET',
		headers: { 'X-WP-Nonce': 'abc' },
	} );
	expect( fetcher.mock.calls[ 1 ][ 0 ] ).toEqual( {
		url: 'u',
		method: 'GET',
		headers: {},
		signal,
	} );
} );

describe( 'search', () => {
	test( 'answers with results, from the URL for the term', async () => {
		const fetcher = vi.fn( () =>
			Promise.resolve( [ { value: 4, label: 'Home' } ] )
		);
		const source = sourceOf( post(), REST );
		const found = search( fetcher, source, 'ho', 'n' );

		await expect( found.promise ).resolves.toEqual( [
			{ id: 4, text: 'Home' },
		] );
		expect( fetcher.mock.calls[ 0 ][ 0 ].url ).toBe(
			`${ REST }/posts?search=ho&post_type=page`
		);
		expect( fetcher.mock.calls[ 0 ][ 0 ].headers ).toEqual( {
			'X-WP-Nonce': 'n',
		} );
		expect( found.aborted() ).toBe( false );
	} );

	test( 'a search replaced by the next is cancelled, and says so', () => {
		let signal;
		const fetcher = ( options ) => {
			signal = options.signal;

			return new Promise( () => {} );
		};
		const found = search( fetcher, sourceOf( post(), REST ), 'h', '' );

		found.abort();

		expect( signal.aborted ).toBe( true );
		expect( found.aborted() ).toBe( true );
	} );

	test( 'a failed search rejects', async () => {
		const found = search(
			() => Promise.reject( { code: 'rest_forbidden' } ),
			sourceOf( post(), REST ),
			'h',
			''
		);

		await expect( found.promise ).rejects.toEqual( {
			code: 'rest_forbidden',
		} );
	} );
} );

describe( "labelling a saved rule's values", () => {
	test( "the options become the routes', all chosen, in its order", async () => {
		const element = post();
		const refreshed = vi.fn();
		const fetcher = vi.fn( () =>
			Promise.resolve( [
				{ value: 9, label: 'Shop' },
				{ value: 4, label: 'Home' },
			] )
		);

		await expect(
			label( fetcher, element, sourceOf( element, REST ), 'n', refreshed )
		).resolves.toBe( true );

		expect( fetcher.mock.calls[ 0 ][ 0 ] ).toEqual( {
			url: `${ REST }/posts?include=4%2C9&post_type=page`,
			method: 'GET',
			headers: { 'X-WP-Nonce': 'n' },
		} );
		expect(
			[ ...element.options ].map( ( o ) => [
				o.value,
				o.text,
				o.selected,
			] )
		).toEqual( [
			[ '9', 'Shop', true ],
			[ '4', 'Home', true ],
		] );
		expect( refreshed ).toHaveBeenCalledWith( element );
	} );

	test( 'a single select is labelled too', async () => {
		const element = custom();
		const fetcher = vi.fn( () =>
			Promise.resolve( [ { value: 'a@b.c', label: 'Ann <a@b.c>' } ] )
		);

		await label(
			fetcher,
			element,
			sourceOf( element, REST ),
			'',
			() => {}
		);

		expect( fetcher.mock.calls[ 0 ][ 0 ].url ).toBe(
			`${ REST }/ajax?set_id=discount_rule&condition_id=customer&include=a%40b.c`
		);
		expect( element.value ).toBe( 'a@b.c' );
		expect( element.options[ 0 ].text ).toBe( 'Ann <a@b.c>' );
	} );

	test( 'nothing chosen asks nothing', async () => {
		const element = select(
			'<select class="conditions-ajax-select" multiple data-type="post"></select>'
		);
		const fetcher = vi.fn();

		await expect(
			label( fetcher, element, sourceOf( element, REST ), '', () => {} )
		).resolves.toBe( false );
		expect( fetcher ).not.toHaveBeenCalled();
	} );

	test( 'a failed request leaves the values as they are', async () => {
		const element = post();
		const refreshed = vi.fn();

		await expect(
			label(
				() => Promise.reject( { code: 'callback_error' } ),
				element,
				sourceOf( element, REST ),
				'',
				refreshed
			)
		).resolves.toBe( false );

		expect(
			[ ...element.selectedOptions ].map( ( o ) => o.value )
		).toEqual( [ '4', '9' ] );
		expect( refreshed ).not.toHaveBeenCalled();
	} );
} );
