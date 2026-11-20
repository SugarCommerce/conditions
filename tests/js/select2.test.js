/**
 * The Select2 adapter: the one place jQuery is reached, and nothing at all
 * without it.
 */

import { afterEach, describe, expect, test, vi } from 'vitest';

import {
	available,
	destroyWithin,
	enhance,
	isEnhanced,
	onChange,
	refresh,
} from '../../src/js/lib/select2';
import { on } from '../../src/js/lib/dom';
import { tick, withoutJQuery, withSelect2 } from './support/page';

const select = ( markup = '<option value="a">A</option>' ) => {
	document.body.innerHTML = `<div id="root"><select>${ markup }</select></div>`;

	return document.querySelector( 'select' );
};

afterEach( () => {
	document.body.innerHTML = '';
	withoutJQuery();
} );

describe( 'without jQuery', () => {
	test( 'nothing is enhanced, refreshed or destroyed, and nothing throws', () => {
		withoutJQuery();

		const element = select();

		expect( available() ).toBe( false );
		expect( enhance( element, { placeholder: 'x' } ) ).toBe( false );
		expect( isEnhanced( element ) ).toBe( false );
		expect( () => refresh( element ) ).not.toThrow();
		expect( () => destroyWithin( document.body ) ).not.toThrow();
	} );

	test( 'jQuery without Select2 is no Select2', () => {
		window.jQuery = Object.assign( () => {}, { fn: {} } );

		expect( available() ).toBe( false );
		expect( enhance( select(), {} ) ).toBe( false );
	} );

	test( 'a change is heard natively', () => {
		withoutJQuery();

		const element = select();
		const handler = vi.fn();

		onChange( document.getElementById( 'root' ), 'select', handler, on );
		element.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		expect( handler ).toHaveBeenCalledTimes( 1 );
		expect( handler.mock.calls[ 0 ][ 1 ] ).toBe( element );
	} );
} );

describe( 'with Select2', () => {
	test( 'a select is enhanced once, with what it was asked for', () => {
		const $ = withSelect2();
		const element = select(
			'<option value=""></option><option value="a">A</option>'
		);
		const spy = vi.spyOn( $.fn, 'select2' );

		expect(
			enhance( element, { placeholder: 'Pick', allowClear: true } )
		).toBe( true );
		expect( enhance( element, { placeholder: 'Again' } ) ).toBe( false );
		expect( spy ).toHaveBeenCalledTimes( 1 );
		expect( spy.mock.calls[ 0 ][ 0 ] ).toEqual( {
			width: '100%',
			allowClear: true,
			placeholder: 'Pick',
		} );
		expect( isEnhanced( element ) ).toBe( true );

		destroyWithin( document.body );
		expect( isEnhanced( element ) ).toBe( false );
	} );

	test( 'free entry becomes tags, separators and createTag', () => {
		const $ = withSelect2();
		const spy = vi.spyOn( $.fn, 'select2' );
		const create = vi.fn( ( term ) => ( term ? { id: term } : null ) );

		enhance( select(), {
			freeEntry: { separators: [ ',' ], create },
			labelSelection: () => 'x',
		} );

		const options = spy.mock.calls[ 0 ][ 0 ];

		expect( options.tags ).toBe( true );
		expect( options.tokenSeparators ).toEqual( [ ',' ] );
		expect( options.templateSelection() ).toBe( 'x' );
		expect( options.createTag( { term: 'a' } ) ).toEqual( { id: 'a' } );
		expect( create ).toHaveBeenCalledWith( 'a' );
	} );

	test( 'a search is Select2 ajax: after its delay, cancelled as status 0', async () => {
		const $ = withSelect2();
		const spy = vi.spyOn( $.fn, 'select2' );
		let settle;
		const request = {
			promise: new Promise(
				( resolve, reject ) => ( settle = { resolve, reject } )
			),
			abort: vi.fn(),
			aborted: vi.fn( () => false ),
		};
		const find = vi.fn( () => request );

		enhance( select(), { search: find } );

		const ajax = spy.mock.calls[ 0 ][ 0 ].ajax;
		const success = vi.fn();
		const failure = vi.fn();

		expect( ajax.delay ).toBe( 250 );
		expect( ajax.data( { term: 'ho' } ) ).toEqual( { term: 'ho' } );
		expect( ajax.processResults( { results: [ 1 ] } ) ).toEqual( {
			results: [ 1 ],
		} );

		const handle = ajax.transport(
			{ data: { term: 'ho' } },
			success,
			failure
		);

		expect( find ).toHaveBeenCalledWith( 'ho' );
		expect( handle.status ).toBe( 500 );

		handle.abort();
		expect( request.abort ).toHaveBeenCalled();

		request.aborted.mockReturnValue( true );
		expect( handle.status ).toBe( 0 );

		settle.resolve( [ { id: 1, text: 'A' } ] );
		await tick();
		expect( success ).toHaveBeenCalledWith( {
			results: [ { id: 1, text: 'A' } ],
		} );
		expect( failure ).not.toHaveBeenCalled();
	} );

	test( 'a failed search reaches Select2 as a failure', async () => {
		const $ = withSelect2();
		const spy = vi.spyOn( $.fn, 'select2' );

		enhance( select(), {
			search: () => ( {
				promise: Promise.reject( new Error( 'no' ) ),
				abort() {},
				aborted: () => false,
			} ),
		} );

		const failure = vi.fn();

		spy.mock.calls[ 0 ][ 0 ].ajax.transport(
			{ data: { term: '' } },
			() => {},
			failure
		);
		await tick();

		expect( failure ).toHaveBeenCalledTimes( 1 );
	} );

	test( "Select2's change and a native one are each heard once", () => {
		const $ = withSelect2();
		const element = select();
		const handler = vi.fn();
		const fallback = vi.fn();

		onChange(
			document.getElementById( 'root' ),
			'select',
			handler,
			fallback
		);

		$( element ).trigger( 'change' );
		element.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		expect( fallback ).not.toHaveBeenCalled();
		expect( handler ).toHaveBeenCalledTimes( 2 );
		expect( handler.mock.calls[ 0 ][ 1 ] ).toBe( element );
		expect( handler.mock.calls[ 1 ][ 1 ] ).toBe( element );
	} );

	test( 'refresh tells Select2 to show the select again, and only Select2', () => {
		const $ = withSelect2();
		const element = select(
			'<option value="a">A</option><option value="b">B</option>'
		);
		const heard = vi.fn();

		enhance( element, { placeholder: '' } );
		$( element ).on( 'change', heard );

		element.value = 'b';
		refresh( element );

		expect(
			document.querySelector( '.select2-selection__rendered' ).textContent
		).toBe( 'B' );
		expect( heard ).not.toHaveBeenCalled();
	} );
} );
