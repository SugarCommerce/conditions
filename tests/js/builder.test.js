/**
 * The builder on the page PHP draws, with the bundled Select2 on it as in the
 * admin: groups and rows added, copied and removed, every field type drawn
 * from a saved rule and posted back exactly as it was saved.
 */

import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';

import { init, savedGroups } from '../../assets/js/builder/builder';
import {
	build,
	editorPage,
	fakeRoutes,
	posted,
	tick,
	withoutJQuery,
	withSelect2,
} from './support/page';

const LABELS = {
	posts: ( q ) =>
		( q.include ? q.include.split( ',' ) : [ '4' ] ).map( ( id ) => ( {
			value: Number( id ),
			label: `Page ${ id }`,
		} ) ),
	terms: ( q ) =>
		( q.include ? q.include.split( ',' ) : [ '3' ] ).map( ( id ) => ( {
			value: Number( id ),
			label: `Term ${ id }`,
		} ) ),
	users: ( q ) =>
		( q.include ? q.include.split( ',' ) : [ '1' ] ).map( ( id ) => ( {
			value: Number( id ),
			label: `User ${ id }`,
		} ) ),
	ajax: ( q ) =>
		( q.include ? q.include.split( ',' ) : [ 'ann@x.com' ] ).map(
			( email ) => ( { value: email, label: `Name <${ email }>` } )
		),
};

/**
 * One saved rule of every field type, in one group, and a second group.
 */
const SAVED = [
	{
		id: 'g1',
		match: 'all',
		rules: [
			{
				id: 'r1',
				condition: 'coupon',
				operator: 'starts_with',
				value: 'SPRING',
			},
			{ id: 'r2', condition: 'total', operator: '>', value: 0 },
			{
				id: 'r3',
				condition: 'weight',
				operator: '>=',
				value: { number: 3, unit: 'kg' },
			},
			{
				id: 'r4',
				condition: 'gift',
				operator: '==',
				value: { text: 'Alex & "Sam"', unit: 'g' },
			},
			{ id: 'r5', condition: 'is_gift', operator: 'no', value: null },
			{
				id: 'r6',
				condition: 'deliver',
				operator: '==',
				value: '2026-12-24',
			},
			{ id: 'r7', condition: 'pickup', operator: '==', value: '09:30' },
			{ id: 'r8', condition: 'colour', operator: '!=', value: 'blue' },
			{
				id: 'r9',
				condition: 'sizes',
				operator: 'any',
				value: [ 'm', 'retired' ],
			},
			{
				id: 'r10',
				condition: 'tags',
				operator: 'any_exact',
				value: [ 'vip', 'gold' ],
			},
			{
				id: 'r11',
				condition: 'ip',
				operator: 'ip_match',
				value: [ '10.0.0.0/8', '::1' ],
			},
			{
				id: 'r12',
				condition: 'email',
				operator: 'email_match',
				value: [ '@x.com' ],
			},
			{ id: 'r13', condition: 'page', operator: 'any', value: [ 4, 9 ] },
			{ id: 'r14', condition: 'category', operator: '==', value: '3' },
			{
				id: 'r15',
				condition: 'manager',
				operator: 'any',
				value: [ '1' ],
			},
			{
				id: 'r16',
				condition: 'customer',
				operator: 'none',
				value: [ 'ann@x.com' ],
			},
		],
	},
	{
		id: 'g2',
		match: 'all',
		rules: [ { id: 'r17', condition: 'total', operator: '<', value: 5 } ],
	},
];

/**
 * What the form posts for the saved rules above.
 */
const POSTED = [
	[ 'coupon', 'starts_with', [ [ '[value]', 'SPRING' ] ] ],
	[ 'total', '>', [ [ '[value]', '0' ] ] ],
	[
		'weight',
		'>=',
		[
			[ '[value][number]', '3' ],
			[ '[value][unit]', 'kg' ],
		],
	],
	[
		'gift',
		'==',
		[
			[ '[value][text]', 'Alex & "Sam"' ],
			[ '[value][unit]', 'g' ],
		],
	],
	[ 'is_gift', 'no', [] ],
	[ 'deliver', '==', [ [ '[value]', '2026-12-24' ] ] ],
	[ 'pickup', '==', [ [ '[value]', '09:30' ] ] ],
	[ 'colour', '!=', [ [ '[value]', 'blue' ] ] ],
	[
		'sizes',
		'any',
		[
			[ '[value][]', 'm' ],
			[ '[value][]', 'retired' ],
		],
	],
	[
		'tags',
		'any_exact',
		[
			[ '[value][]', 'vip' ],
			[ '[value][]', 'gold' ],
		],
	],
	[
		'ip',
		'ip_match',
		[
			[ '[value][]', '10.0.0.0/8' ],
			[ '[value][]', '::1' ],
		],
	],
	[ 'email', 'email_match', [ [ '[value][]', '@x.com' ] ] ],
	[
		'page',
		'any',
		[
			[ '[value][]', '4' ],
			[ '[value][]', '9' ],
		],
	],
	[ 'category', '==', [ [ '[value]', '3' ] ] ],
	[ 'manager', 'any', [ [ '[value][]', '1' ] ] ],
	[ 'customer', 'none', [ [ '[value][]', 'ann@x.com' ] ] ],
];

/**
 * The pairs a group's rules post, ids as posted() normalises them.
 *
 * @param {string} group The group's short id.
 * @param {number} first The short id of its first rule.
 * @param {Array}  rules [ condition, operator, [ [ suffix, value ] ] ].
 * @return {Array} The pairs.
 */
const pairs = ( group, first, rules ) =>
	rules.flatMap( ( [ condition, operator, values ], index ) => {
		const base = `_conditions[${ group }][rules][r${ first + index }]`;

		return [
			[ `${ base }[condition]`, condition ],
			[ `${ base }[operator]`, operator ],
			...values.map( ( [ suffix, value ] ) => [ base + suffix, value ] ),
		];
	} );

const $ = () => window.jQuery;
const groups = () => [ ...document.querySelectorAll( '.condition-group' ) ];
const rowsOf = ( group ) => [ ...group.querySelectorAll( '.condition-row' ) ];
const click = ( element ) => element.click();

/**
 * Choose a condition in a row as Select2 does: set it, trigger jQuery's change.
 *
 * @param {Element} row The row.
 * @param {string}  id  The condition.
 */
function choose( row, id ) {
	$()( row.querySelector( '.condition-select' ) )
		.val( id )
		.trigger( 'change' );
}

let fetcher;

beforeEach( () => {
	withSelect2();
	fetcher = fakeRoutes( LABELS );
} );

afterEach( () => {
	document.body.innerHTML = '';
	delete window.SugarCommerceConditions;
	withoutJQuery();
	vi.useRealTimers();
} );

describe( 'a new rule', () => {
	test( 'starts as one group of one empty row that cannot be removed', () => {
		editorPage();
		init( fetcher, build );

		const [ group ] = groups();
		const [ row ] = rowsOf( group );

		expect( groups() ).toHaveLength( 1 );
		expect( group.querySelector( '.group-label' ).textContent ).toBe(
			'Match all of the following rules'
		);
		expect( group.getAttribute( 'data-group-id' ) ).toMatch(
			/^[a-z0-9]{1,9}$/
		);
		expect( row.querySelector( '.condition-select' ).name ).toBe(
			`_conditions[${ group.getAttribute( 'data-group-id' ) }][rules][${ row.getAttribute( 'data-condition-id' ) }][condition]`
		);
		expect( row.querySelector( '.operator-select' ).name ).toMatch(
			/\]\[operator\]$/
		);
		expect( row.querySelector( '.operator-select' ).disabled ).toBe( true );
		expect( row.querySelector( '.remove-condition' ).disabled ).toBe(
			true
		);
		expect(
			row
				.querySelector( '.remove-condition' )
				.classList.contains( 'disabled' )
		).toBe( true );
		expect(
			row
				.querySelector( '.condition-select' )
				.classList.contains( 'select2-hidden-accessible' )
		).toBe( true );
		expect( posted() ).toEqual( [
			[ '_conditions[g0][rules][r0][condition]', '' ],
		] );
	} );

	test( 'choosing a condition offers its operators, its field and its description', () => {
		editorPage();
		init( fetcher, build );

		const [ row ] = rowsOf( groups()[ 0 ] );

		choose( row, 'total' );

		const operator = row.querySelector( '.operator-select' );
		const input = row.querySelector( '.value-field-wrapper input' );

		expect( operator.disabled ).toBe( false );
		expect( [ ...operator.options ].map( ( o ) => o.value ) ).toEqual(
			Object.keys(
				window.SugarCommerceConditions[ build ].discount_rule.operators
					.number
			)
		);
		expect( operator.value ).toBe( operator.options[ 0 ].value );
		expect( [ input.type, input.min, input.step ] ).toEqual( [
			'number',
			'0',
			'0.01',
		] );
		expect(
			row.querySelector( '.condition-tooltip' ).getAttribute( 'data-tip' )
		).toBe( 'Select a condition to see what it matches.' );

		choose( row, 'coupon' );

		expect(
			row.querySelector( '.condition-tooltip' ).getAttribute( 'data-tip' )
		).toBe( 'The coupon code.' );
		expect(
			row.querySelector( '.value-field-wrapper input' ).placeholder
		).toBe( 'e.g. SPRING' );
		expect(
			row.querySelector( '.select2-selection__rendered' ).textContent
		).toBe( 'Cart → Coupon' );
	} );

	test( 'a field that was a picker is undone before the next is drawn', () => {
		editorPage();
		init( fetcher, build );

		const [ row ] = rowsOf( groups()[ 0 ] );

		choose( row, 'sizes' );
		expect(
			row.querySelectorAll( '.value-field-wrapper .select2-container' )
		).toHaveLength( 1 );

		const destroy = vi.spyOn( $().fn, 'select2' );

		choose( row, 'tags' );

		expect( destroy ).toHaveBeenCalledWith( 'destroy' );
		expect(
			row.querySelectorAll( '.value-field-wrapper .select2-container' )
		).toHaveLength( 1 );
		expect(
			row.querySelector( '.value-field-wrapper select' ).className
		).toContain( 'conditions-tags-select' );
	} );
} );

describe( 'rows and groups', () => {
	test( 'Add Condition adds a row, and rows can then be removed down to one', () => {
		editorPage();
		init( fetcher, build );

		const [ group ] = groups();

		click( group.querySelector( '.add-condition' ) );
		click( group.querySelector( '.add-condition' ) );

		expect( rowsOf( group ) ).toHaveLength( 3 );
		expect(
			rowsOf( group ).every(
				( row ) => ! row.querySelector( '.remove-condition' ).disabled
			)
		).toBe( true );
		expect(
			new Set( rowsOf( group ).map( ( row ) => row.dataset.conditionId ) )
				.size
		).toBe( 3 );

		click(
			rowsOf( group )[ 1 ].querySelector( '.remove-condition .dashicons' )
		);
		click( rowsOf( group )[ 1 ].querySelector( '.remove-condition' ) );

		expect( rowsOf( group ) ).toHaveLength( 1 );
		expect(
			rowsOf( group )[ 0 ].querySelector( '.remove-condition' ).disabled
		).toBe( true );
	} );

	test( "a group's only row stays, even when asked through a stale button", () => {
		editorPage();
		init( fetcher, build );

		const button = groups()[ 0 ].querySelector( '.remove-condition' );

		button.disabled = false;
		click( button );

		expect( rowsOf( groups()[ 0 ] ) ).toHaveLength( 1 );
	} );

	test( 'Add Group adds an OR and an "Or match" group', () => {
		editorPage();
		init( fetcher, build );

		click( document.querySelector( '.add-group' ) );

		expect(
			[ ...document.querySelector( '.condition-groups' ).children ].map(
				( el ) => el.className
			)
		).toEqual( [
			'condition-group',
			'group-connector',
			'condition-group',
		] );
		expect( document.querySelector( '.group-connector' ).textContent ).toBe(
			'OR'
		);
		expect(
			groups()[ 1 ].querySelector( '.group-label' ).textContent
		).toBe( 'Or match all of the following rules' );
	} );

	test( 'deleting a group takes one OR with it, wherever it was', () => {
		editorPage();
		init( fetcher, build );

		const add = document.querySelector( '.add-group' );
		const layout = () =>
			[ ...document.querySelector( '.condition-groups' ).children ].map(
				( el ) =>
					el.className === 'group-connector'
						? 'OR'
						: el.dataset.groupId
			);

		click( add );
		click( add );
		click( add );

		const [ a, b, c, d ] = groups().map( ( g ) => g.dataset.groupId );

		click( groups()[ 1 ].querySelector( '.delete-group' ) );
		expect( layout() ).toEqual( [ a, 'OR', c, 'OR', d ] );

		click( groups()[ 0 ].querySelector( '.delete-group' ) );
		expect( layout() ).toEqual( [ c, 'OR', d ] );
		expect(
			groups()[ 0 ].querySelector( '.group-label' ).textContent
		).toBe( 'Match all of the following rules' );

		click( groups()[ 1 ].querySelector( '.delete-group' ) );
		expect( layout() ).toEqual( [ c ] );
		expect( b ).not.toBe( c );
	} );

	test( 'deleting the last group leaves a new empty one', () => {
		editorPage();
		window.SugarCommerceConditions[ build ].discount_rule.i18n.matchAll =
			'Alle diese Regeln';
		init( fetcher, build );

		const first = groups()[ 0 ].dataset.groupId;

		click( groups()[ 0 ].querySelector( '.delete-group' ) );

		expect( groups() ).toHaveLength( 1 );
		expect( groups()[ 0 ].dataset.groupId ).not.toBe( first );
		expect( rowsOf( groups()[ 0 ] ) ).toHaveLength( 1 );
		expect(
			groups()[ 0 ].querySelector( '.group-label' ).textContent
		).toBe( 'Alle diese Regeln' );
		expect( document.querySelector( '.group-connector' ) ).toBeNull();
	} );

	test( 'the links do not follow their href', () => {
		editorPage();
		init( fetcher, build );

		const event = new MouseEvent( 'click', {
			bubbles: true,
			cancelable: true,
		} );

		groups()[ 0 ]
			.querySelector( '.duplicate-group' )
			.dispatchEvent( event );

		expect( event.defaultPrevented ).toBe( true );
	} );
} );

describe( 'a saved rule', () => {
	test( 'is drawn with every value, and posts exactly what was saved', async () => {
		editorPage( SAVED );

		const [ builder ] = init( fetcher, build );

		await builder.settled();

		const [ first, second ] = groups();

		expect( first.dataset.groupId ).toBe( 'g1' );
		expect( rowsOf( first ).map( ( r ) => r.dataset.conditionId ) ).toEqual(
			SAVED[ 0 ].rules.map( ( r ) => r.id )
		);
		expect( second.querySelector( '.group-label' ).textContent ).toBe(
			'Or match all of the following rules'
		);
		expect( posted() ).toEqual( [
			...pairs( 'g0', 0, POSTED ),
			...pairs( 'g1', 16, [ [ 'total', '<', [ [ '[value]', '5' ] ] ] ] ),
		] );
	} );

	test( 'the searched fields show labels, asked once each with the nonce', async () => {
		editorPage( SAVED );
		await init( fetcher, build )[ 0 ].settled();

		const rest =
			window.SugarCommerceConditions[ build ].discount_rule.restUrl;

		expect( fetcher.calls.map( ( c ) => c.url ) ).toEqual( [
			`${ rest }/posts?include=4%2C9&post_type=page`,
			`${ rest }/terms?include=3&taxonomy=category`,
			`${ rest }/users?include=1&role=administrator%2Ceditor`,
			`${ rest }/ajax?set_id=discount_rule&condition_id=customer&include=ann%40x.com`,
		] );
		expect(
			fetcher.calls.every(
				( c ) => c.headers[ 'X-WP-Nonce' ] === 'nonce:wp_rest'
			)
		).toBe( true );

		const shown = ( condition ) =>
			[
				...document.querySelectorAll(
					`.condition-row[data-condition-id="${ condition }"] .value-field-wrapper .select2-selection__choice, .condition-row[data-condition-id="${ condition }"] .value-field-wrapper .select2-selection__rendered`
				),
			]
				.map( ( el ) => el.getAttribute( 'title' ) )
				.filter( Boolean );

		expect( shown( 'r13' ) ).toEqual( [ 'Page 4', 'Page 9' ] );
		expect( shown( 'r14' ) ).toEqual( [ 'Term 3' ] );
		expect( shown( 'r15' ) ).toEqual( [ 'User 1' ] );
		expect( shown( 'r16' ) ).toEqual( [ 'Name <ann@x.com>' ] );
	} );

	test( 'a picker whose labels cannot be fetched keeps its values', async () => {
		editorPage( SAVED );
		await init( fakeRoutes( {} ), build )[ 0 ].settled();

		expect( posted() ).toEqual( [
			...pairs( 'g0', 0, POSTED ),
			...pairs( 'g1', 16, [ [ 'total', '<', [ [ '[value]', '5' ] ] ] ] ),
		] );
	} );

	test( 'Duplicate copies a group as it stands, with ids of its own', async () => {
		editorPage( SAVED );

		const [ builder ] = init( fetcher, build );

		await builder.settled();
		click( groups()[ 0 ].querySelector( '.duplicate-group' ) );
		await builder.settled();

		const copy = groups()[ 2 ];

		expect( groups() ).toHaveLength( 3 );
		expect( copy.dataset.groupId ).not.toBe( 'g1' );
		expect(
			rowsOf( copy ).map( ( r ) => r.dataset.conditionId )
		).not.toContain( 'r1' );
		expect( posted().slice( -pairs( 'g2', 17, POSTED ).length ) ).toEqual(
			pairs( 'g2', 17, POSTED )
		);
	} );

	test( 'a rule for a condition the set no longer has asks for one', async () => {
		editorPage( [
			{
				id: 'g',
				rules: [
					{
						id: 'r',
						condition: 'retired',
						operator: '==',
						value: 'x',
					},
				],
			},
		] );
		await init( fetcher, build )[ 0 ].settled();

		const [ row ] = rowsOf( groups()[ 0 ] );

		expect( row.querySelector( '.condition-select' ).value ).toBe( '' );
		expect( row.querySelector( '.operator-select' ).disabled ).toBe( true );
		expect(
			row.querySelector( '.value-field-wrapper input' ).disabled
		).toBe( true );
	} );

	test( 'the saved groups are read from the data PHP wrote, or none', () => {
		const container = document.createElement( 'div' );

		container.setAttribute( 'data-conditions', '[{"id":"a"}]' );
		expect( savedGroups( container ) ).toEqual( [ { id: 'a' } ] );

		container.setAttribute( 'data-conditions', '{"a":1}' );
		expect( savedGroups( container ) ).toEqual( [] );

		container.setAttribute( 'data-conditions', 'not json' );
		expect( savedGroups( container ) ).toEqual( [] );

		container.removeAttribute( 'data-conditions' );
		expect( savedGroups( container ) ).toEqual( [] );
	} );
} );

describe( 'typing entries', () => {
	/**
	 * Type into a free-entry select's search box, as a person would.
	 *
	 * @param {Element} row  The row.
	 * @param {string}  text What is typed.
	 */
	function type( row, text ) {
		const select = row.querySelector( '.value-field-wrapper select' );

		$()( select ).select2( 'open' );

		const field = row.querySelector( '.select2-search__field' );

		field.value = text;
		field.dispatchEvent( new Event( 'input', { bubbles: true } ) );
	}

	const chosen = ( row ) =>
		[
			...row.querySelector( '.value-field-wrapper select' )
				.selectedOptions,
		].map( ( o ) => o.value );

	test( 'tags split on commas and spaces', () => {
		editorPage();
		init( fetcher, build );

		const [ row ] = rowsOf( groups()[ 0 ] );

		choose( row, 'tags' );
		type( row, 'vip,gold silver ' );

		expect( chosen( row ) ).toEqual( [ 'vip', 'gold', 'silver' ] );
	} );

	test( 'IPs take addresses and ranges and refuse the rest', () => {
		editorPage();
		init( fetcher, build );

		const [ row ] = rowsOf( groups()[ 0 ] );

		choose( row, 'ip' );
		type( row, '10.0.0.0/8,2001:db8::1 bad_ip,' );

		expect( chosen( row ) ).toEqual( [ '10.0.0.0/8', '2001:db8::1' ] );
		// A refused entry stays in the box to be corrected.
		expect( row.querySelector( '.select2-search__field' ).value ).toBe(
			'bad_ip,'
		);
	} );

	test( 'email patterns split only on commas', () => {
		editorPage();
		init( fetcher, build );

		const [ row ] = rowsOf( groups()[ 0 ] );

		choose( row, 'email' );
		type( row, '@x.com,user@shop.test .edu,' );

		expect( chosen( row ) ).toEqual( [ '@x.com' ] );
		expect( row.querySelector( '.select2-search__field' ).value ).toBe(
			'user@shop.test .edu,'
		);

		type( row, 'user@shop.test,.edu,' );

		expect( chosen( row ) ).toEqual( [
			'@x.com',
			'user@shop.test',
			'.edu',
		] );
	} );

	test( "a value picker shows its field's placeholder", () => {
		editorPage();
		init( fetcher, build );

		const [ row ] = rowsOf( groups()[ 0 ] );

		choose( row, 'tags' );

		expect(
			row
				.querySelector( '.select2-search__field' )
				.getAttribute( 'placeholder' )
		).toBe( 'Type and press Enter…' );

		choose( row, 'colour' );

		expect(
			row.querySelector(
				'.value-field-wrapper .select2-selection__placeholder'
			).textContent
		).toBe( 'Select...' );
	} );

	test( "the open search box turns off the browser's suggestions", () => {
		editorPage();
		init( fetcher, build );

		const [ row ] = rowsOf( groups()[ 0 ] );

		$()( row.querySelector( '.condition-select' ) ).select2( 'open' );

		const field = document.querySelector(
			'.select2-container--open .select2-search__field'
		);

		expect( [
			field.getAttribute( 'autocomplete' ),
			field.getAttribute( 'autocorrect' ),
			field.getAttribute( 'autocapitalize' ),
			field.getAttribute( 'spellcheck' ),
		] ).toEqual( [ 'off', 'off', 'off', 'false' ] );
	} );
} );

describe( 'searching', () => {
	test( 'opening a picker searches its route; a typed term waits a moment', async () => {
		editorPage();
		init( fetcher, build );

		const [ row ] = rowsOf( groups()[ 0 ] );
		const rest =
			window.SugarCommerceConditions[ build ].discount_rule.restUrl;

		choose( row, 'page' );

		const select = row.querySelector( '.value-field-wrapper select' );

		$()( select ).select2( 'open' );
		await tick();

		expect( fetcher.calls.map( ( c ) => c.url ) ).toEqual( [
			`${ rest }/posts?post_type=page`,
		] );
		expect(
			[ ...document.querySelectorAll( '.select2-results__option' ) ].map(
				( li ) => li.textContent
			)
		).toEqual( [ 'Page 4' ] );

		const field = row.querySelector( '.select2-search__field' );

		field.value = 'ho';
		field.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		await tick( 100 );
		expect( fetcher.calls ).toHaveLength( 1 );

		await tick( 250 );
		expect( fetcher.calls.map( ( c ) => c.url ) ).toEqual( [
			`${ rest }/posts?post_type=page`,
			`${ rest }/posts?search=ho&post_type=page`,
		] );

		document
			.querySelector( '.select2-results__option' )
			.dispatchEvent( new MouseEvent( 'mouseup', { bubbles: true } ) );

		expect(
			[ ...select.selectedOptions ].map( ( o ) => [ o.value, o.text ] )
		).toEqual( [ [ '4', 'Page 4' ] ] );
	} );

	test( "a condition's own search asks with its set and id", async () => {
		editorPage();
		init( fetcher, build );

		const [ row ] = rowsOf( groups()[ 0 ] );

		choose( row, 'customer' );
		$()( row.querySelector( '.value-field-wrapper select' ) ).select2(
			'open'
		);
		await tick();

		expect( fetcher.calls.map( ( c ) => c.url ) ).toEqual( [
			`${ window.SugarCommerceConditions[ build ].discount_rule.restUrl }/ajax?set_id=discount_rule&condition_id=customer`,
		] );
	} );

	test( 'a failed search says so', async () => {
		editorPage();
		init( fakeRoutes( {} ), build );

		const [ row ] = rowsOf( groups()[ 0 ] );

		choose( row, 'page' );
		$()( row.querySelector( '.value-field-wrapper select' ) ).select2(
			'open'
		);
		await tick();

		expect(
			document.querySelector( '.select2-results__message' ).textContent
		).toBe( 'The results could not be loaded.' );
	} );
} );

describe( 'without jQuery', () => {
	test( 'the builder works with plain selects', async () => {
		withoutJQuery();
		editorPage( SAVED );

		const [ builder ] = init( fetcher, build );

		await builder.settled();

		expect( document.querySelector( '.select2-container' ) ).toBeNull();
		expect( posted() ).toEqual( [
			...pairs( 'g0', 0, POSTED ),
			...pairs( 'g1', 16, [ [ 'total', '<', [ [ '[value]', '5' ] ] ] ] ),
		] );

		const [ row ] = rowsOf( groups()[ 1 ] );
		const select = row.querySelector( '.condition-select' );

		select.value = 'colour';
		select.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		expect(
			row.querySelector( '.value-field-wrapper select' ).name
		).toMatch( /\[value\]$/ );
		expect(
			[
				...row.querySelector( '.value-field-wrapper select' ).options,
			].map( ( o ) => o.value )
		).toEqual( [ '', 'red', 'blue' ] );
	} );
} );

test( 'a page without the builder is left alone', () => {
	document.body.innerHTML = '<p>Another screen</p>';

	expect( init( fetcher, 'shop-conditions-admin' ) ).toEqual( [] );
} );

test( 'two builds on one page each start only their own builder, from their own data and templates', () => {
	editorPage();

	// A second plugin's copy: another handle, the same set id, its own data
	// and its own row template -- with a condition the first set lacks.
	const mine = document.querySelector( '.conditions-builder' );
	const theirs = mine.cloneNode( true );
	const other = 'other-conditions-admin';

	theirs.setAttribute( 'data-build', other );
	theirs
		.querySelector( 'template[data-template="condition-row"]' )
		.content.querySelector( '.condition-select' )
		.append( new window.Option( 'Their Condition', 'theirs' ) );
	mine.after( theirs );

	window.SugarCommerceConditions[ other ] = {
		discount_rule: {
			...window.SugarCommerceConditions[ build ].discount_rule,
			restUrl: 'https://other.test/wp-json/other-conditions/v1',
		},
	};

	const [ first, ...rest ] = init( fetcher, build );

	expect( rest ).toEqual( [] );
	expect( first.builder ).toBe( mine );
	expect( theirs.querySelectorAll( '.condition-group' ) ).toHaveLength( 0 );
	expect( mine.querySelectorAll( '.condition-group' ) ).toHaveLength( 1 );

	const [ second ] = init( fetcher, other );

	expect( second.builder ).toBe( theirs );
	expect( second.restUrl ).toBe(
		'https://other.test/wp-json/other-conditions/v1'
	);
	expect( first.restUrl ).not.toBe( second.restUrl );
	expect( mine.querySelectorAll( '.condition-group' ) ).toHaveLength( 1 );

	const options = ( builder ) =>
		[
			...builder.querySelector( '.condition-row .condition-select' )
				.options,
		].map( ( option ) => option.value );

	expect( options( theirs ) ).toContain( 'theirs' );
	expect( options( mine ) ).not.toContain( 'theirs' );
} );

test( 'a builder that names no build, or another, is left alone', () => {
	editorPage();

	expect( init( fetcher, '' ) ).toEqual( [] );
	expect( init( fetcher, 'someone-else-conditions-admin' ) ).toEqual( [] );
	expect( document.querySelectorAll( '.condition-group' ) ).toHaveLength( 0 );
} );
