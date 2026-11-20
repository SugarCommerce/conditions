/**
 * The operators a condition offers: its own, or its type's list as
 * Operators::for_type() picks it.
 */

import { execFileSync } from 'node:child_process';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import { describe, expect, test } from 'vitest';

import {
	fillOperators,
	listFor,
	operatorsFor,
} from '../../src/js/builder/operators';

const LISTS = {
	number: { '==': 'is', '>': 'more than' },
	text: { '==': 'is', contains: 'contains' },
	boolean: { yes: 'yes', no: 'no' },
	date: { before: 'before' },
	time: { after: 'after' },
	ip: { ip_match: 'matches' },
	email: { email_match: 'matches' },
	tags: { any_exact: 'any of' },
	collection: { any: 'any of', none: 'none of' },
	collection_basic: { any: 'any of' },
	equality: { '==': 'is', '!=': 'is not' },
};

describe( 'listFor', () => {
	test.each( [
		[ 'number', false, 'number' ],
		[ 'number_unit', false, 'number' ],
		[ 'text_unit', false, 'text' ],
		[ 'boolean', false, 'boolean' ],
		[ 'date', false, 'date' ],
		[ 'time', false, 'time' ],
		[ 'ip', false, 'ip' ],
		[ 'email', false, 'email' ],
		[ 'tags', false, 'tags' ],
		[ 'select', false, 'equality' ],
		[ 'select', true, 'collection' ],
		[ 'user', false, 'equality' ],
		[ 'user', true, 'collection_basic' ],
		[ 'post', false, 'equality' ],
		[ 'post', true, 'collection' ],
		[ 'term', true, 'collection' ],
		[ 'term', false, 'equality' ],
		[ 'ajax', true, 'collection' ],
		[ 'ajax', false, 'equality' ],
		[ 'text', false, 'text' ],
		[ 'something_new', true, 'text' ],
	] )( '%s (multiple: %s) uses %s', ( type, multiple, list ) => {
		expect( listFor( type, multiple ) ).toBe( list );
	} );
} );

describe( 'operatorsFor', () => {
	test( "a condition's own operators win over its type's", () => {
		const own = { starts_with: 'starts with' };

		expect(
			operatorsFor( { type: 'number', operators: own }, LISTS )
		).toBe( own );
	} );

	test( "a condition without its own gets its type's list", () => {
		expect( operatorsFor( { type: 'number' }, LISTS ) ).toBe(
			LISTS.number
		);
		expect(
			operatorsFor( { type: 'select', multiple: true }, LISTS )
		).toBe( LISTS.collection );
		expect( operatorsFor( {}, LISTS ) ).toBe( LISTS.text );
	} );

	test( 'no condition, or a list PHP did not send, is no operators', () => {
		expect( operatorsFor( undefined, LISTS ) ).toEqual( {} );
		expect( operatorsFor( { type: 'ip' }, {} ) ).toEqual( {} );
	} );
} );

describe( 'fillOperators', () => {
	const select = () => {
		const element = document.createElement( 'select' );

		element.innerHTML = '<option value="">Select...</option>';
		element.disabled = true;

		return element;
	};

	test( 'offers the operators in order and chooses the first', () => {
		const element = select();

		fillOperators( element, 'total', LISTS.number );

		expect(
			[ ...element.options ].map( ( o ) => [ o.value, o.text ] )
		).toEqual( [
			[ '==', 'is' ],
			[ '>', 'more than' ],
		] );
		expect( element.value ).toBe( '==' );
		expect( element.disabled ).toBe( false );
	} );

	test( 'keeps a saved operator the condition still offers', () => {
		const element = select();

		fillOperators( element, 'total', LISTS.number, '>' );
		expect( element.value ).toBe( '>' );

		fillOperators( element, 'total', LISTS.number, 'retired' );
		expect( element.value ).toBe( '==' );
	} );

	test( 'is disabled without a condition, or with no operators', () => {
		const element = select();

		fillOperators( element, '', LISTS.number );
		expect( element.disabled ).toBe( true );

		fillOperators( element, 'total', {} );
		expect( element.disabled ).toBe( true );
		expect( element.options ).toHaveLength( 0 );
	} );

	test( 'a label is text, never markup', () => {
		const element = select();

		fillOperators( element, 'x', {
			'<b>': '<img src=x onerror=alert(1)>',
		} );

		expect( element.querySelector( 'img' ) ).toBeNull();
		expect( element.options[ 0 ].text ).toBe(
			'<img src=x onerror=alert(1)>'
		);
		expect( element.value ).toBe( '<b>' );
	} );
} );

test( 'every type gets the list Operators::for_type() gives it in PHP', () => {
	const php = JSON.parse(
		execFileSync(
			'php',
			[
				join(
					dirname( fileURLToPath( import.meta.url ) ),
					'support/operators.php'
				),
			],
			{ encoding: 'utf8' }
		)
	);

	Object.entries( php.byType ).forEach( ( [ type, lists ] ) => {
		expect( {
			type,
			single: operatorsFor( { type }, php.all ),
			multiple: operatorsFor( { type, multiple: true }, php.all ),
		} ).toEqual( { type, ...lists } );
	} );
} );
