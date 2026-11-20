/**
 * Reading what PHP handed the builder, and escaping.
 */

import { afterEach, expect, test } from 'vitest';

import {
	belongsTo,
	build,
	handleOf,
	readConfig,
	settings,
} from '../../assets/js/lib/config';
import { escapeHtml, html, raw } from '../../assets/js/lib/dom';

afterEach( () => {
	delete window.SugarCommerceConditions;
} );

test( "each builder reads its own build's entry for its set", () => {
	const builder = document.createElement( 'div' );
	const mine = { restUrl: 'https://a.test/' };
	const theirs = { restUrl: 'https://b.test/' };

	builder.setAttribute( 'data-set-id', 'discount_rule' );
	window.SugarCommerceConditions = {
		'shop-conditions-admin': {
			fraud_rule: { restUrl: 'x' },
			discount_rule: mine,
		},
		'other-conditions-admin': { discount_rule: theirs },
	};

	expect( readConfig( builder, 'shop-conditions-admin' ) ).toBe( mine );
	expect( readConfig( builder, 'other-conditions-admin' ) ).toBe( theirs );
	expect( readConfig( builder, 'unknown' ) ).toEqual( {} );
	expect( readConfig( null, 'shop-conditions-admin' ) ).toEqual( {} );

	builder.setAttribute( 'data-set-id', 'other' );
	expect( readConfig( builder, 'shop-conditions-admin' ) ).toEqual( {} );

	delete window.SugarCommerceConditions;
	expect( readConfig( builder, 'shop-conditions-admin' ) ).toEqual( {} );
} );

test( "a script's handle is its element's id, less -js; a builder belongs to the build it names", () => {
	const script = document.createElement( 'script' );
	const builder = document.createElement( 'div' );

	script.id = 'shop-conditions-admin-js';
	expect( handleOf( script ) ).toBe( 'shop-conditions-admin' );
	expect( handleOf( document.createElement( 'script' ) ) ).toBe( '' );
	expect( handleOf( null ) ).toBe( '' );

	// No <script> element runs the tests.
	expect( build ).toBe( '' );

	builder.setAttribute( 'data-build', 'shop-conditions-admin' );
	expect( belongsTo( builder, 'shop-conditions-admin' ) ).toBe( true );
	expect( belongsTo( builder, 'other-conditions-admin' ) ).toBe( false );

	builder.removeAttribute( 'data-build' );
	expect( belongsTo( builder, '' ) ).toBe( false );
} );

test( 'every setting is there, even when PHP sent none', () => {
	expect( settings( {} ) ).toEqual( {
		conditions: {},
		operators: {},
		restUrl: '',
		nonce: '',
		i18n: {},
	} );

	const raw2 = {
		conditions: { a: {} },
		operators: { text: {} },
		restUrl: 'u',
		nonce: 'n',
		i18n: { x: 'y' },
	};

	expect( settings( raw2 ) ).toEqual( raw2 );
} );

test( 'every value is escaped, quotes included; raw and nested markup pass', () => {
	const name = '" onfocus="alert(1)';

	expect( String( html`<input value="${ name }">` ) ).toBe(
		'<input value="&quot; onfocus=&quot;alert(1)">'
	);
	expect( escapeHtml( "it's <b>&" ) ).toBe( 'it&#39;s &lt;b&gt;&amp;' );
	expect( escapeHtml( null ) ).toBe( '' );
	expect( escapeHtml( 0 ) ).toBe( '0' );
	expect(
		String(
			html`<p>${ raw( '<b>x</b>' ) }${ html`<i>${ '<' }</i>` }${ [ 'a', '<' ] }${ false }${ null }${ undefined }</p>`
		)
	).toBe( '<p><b>x</b><i>&lt;</i>a&lt;</p>' );
} );
