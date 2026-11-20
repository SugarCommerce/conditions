/**
 * Reading what PHP handed the builder, and escaping.
 */

import { afterEach, expect, test } from 'vitest';

import { readConfig, settings } from '../../assets/js/lib/config';
import { escapeHtml, html, raw } from '../../assets/js/lib/dom';

afterEach( () => {
	delete window.sugarConditions;
} );

test( "each builder reads its own set's entry", () => {
	const builder = document.createElement( 'div' );
	const mine = { restUrl: 'https://a.test/' };

	builder.setAttribute( 'data-set-id', 'discount_rule' );
	window.sugarConditions = {
		fraud_rule: { restUrl: 'x' },
		discount_rule: mine,
	};

	expect( readConfig( builder ) ).toBe( mine );
	expect( readConfig( null ) ).toEqual( {} );

	builder.setAttribute( 'data-set-id', 'other' );
	expect( readConfig( builder ) ).toEqual( {} );

	delete window.sugarConditions;
	expect( readConfig( builder ) ).toEqual( {} );
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
