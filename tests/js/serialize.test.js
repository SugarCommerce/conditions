/**
 * Reading a row back as a saved rule -- what Duplicate copies -- for every
 * value field there is.
 */

import { expect, test } from 'vitest';

import {
	groupRules,
	rowValue,
	selectValue,
} from '../../assets/js/builder/serialize';
import { valueField } from '../../assets/js/builder/fields';

/**
 * A row holding a value field drawn for a condition.
 *
 * @param {Object} condition The condition.
 * @param {*}      value     The saved value.
 * @return {HTMLElement} The row.
 */
function row( condition, value ) {
	const element = document.createElement( 'div' );

	element.className = 'condition-row';
	element.innerHTML = `<select class="condition-select"><option value="c" selected>C</option></select><select class="operator-select"><option value="==">is</option><option value="!=" selected>is not</option></select><div class="value-field-wrapper">${ valueField(
		{ conditions: { c: condition }, i18n: {}, setId: 's' },
		'c',
		'g',
		'r',
		value
	) }</div>`;

	return element;
}

const units = [
	{ value: 'g', label: 'g' },
	{ value: 'kg', label: 'kg' },
];

test.each( [
	[ 'text', { type: 'text' }, 'SPRING', 'SPRING' ],
	[ 'text, empty', { type: 'text' }, '', '' ],
	[ 'number', { type: 'number' }, 42.5, '42.5' ],
	[ 'date', { type: 'date' }, '2026-12-24', '2026-12-24' ],
	[ 'time', { type: 'time' }, '09:30', '09:30' ],
	[
		'number and unit',
		{ type: 'number_unit', units },
		{ number: 3, unit: 'kg' },
		{ number: '3', unit: 'kg' },
	],
	[
		'text and unit',
		{ type: 'text_unit', units },
		{ text: 'Alex', unit: 'g' },
		{ text: 'Alex', unit: 'g' },
	],
	[
		'number and no units',
		{ type: 'number_unit' },
		{ number: 3 },
		{ number: '3', unit: null },
	],
	[
		'single select',
		{ type: 'select', options: { red: 'Red', blue: 'Blue' } },
		'blue',
		'blue',
	],
	[
		'single select, nothing chosen',
		{ type: 'select', options: { red: 'Red' } },
		'',
		'',
	],
	[
		'multiple select',
		{ type: 'select', multiple: true, options: { s: 'S', m: 'M', l: 'L' } },
		[ 'l', 's' ],
		[ 's', 'l' ],
	],
	[
		'multiple select, nothing chosen',
		{ type: 'select', multiple: true, options: { s: 'S' } },
		[],
		[],
	],
	[ 'tags', { type: 'tags' }, [ 'vip', 'gold' ], [ 'vip', 'gold' ] ],
	[ 'ip', { type: 'ip' }, [ '10.0.0.0/8' ], [ '10.0.0.0/8' ] ],
	[ 'email', { type: 'email' }, [ '@x.com' ], [ '@x.com' ] ],
	[ 'post', { type: 'post', multiple: true }, [ '4' ], [ '4' ] ],
	[ 'term, single', { type: 'term' }, '3', '3' ],
	[ 'term, single, nothing', { type: 'term' }, '', null ],
	[ 'ajax', { type: 'ajax', multiple: true }, [ 'a@b.c' ], [ 'a@b.c' ] ],
	[ 'yes/no', { type: 'boolean' }, 'yes', null ],
] )( '%s', ( name, condition, saved, read ) => {
	expect( rowValue( row( condition, saved ) ) ).toEqual( read );
} );

test( 'a row with no condition chosen reads its disabled field', () => {
	expect( rowValue( row( undefined, 'x' ) ) ).toBe( '' );
} );

test( 'a single select with no options is null; a multiple one is none', () => {
	const single = document.createElement( 'select' );
	const multiple = document.createElement( 'select' );

	multiple.multiple = true;

	expect( selectValue( single ) ).toBeNull();
	expect( selectValue( multiple ) ).toEqual( [] );
	expect( selectValue( null ) ).toBeNull();
} );

test( "a group's rules: condition, operator and value, without ids", () => {
	const group = document.createElement( 'div' );

	group.append(
		row( { type: 'text' }, 'SPRING' ),
		row( { type: 'tags' }, [ 'a' ] )
	);

	expect( groupRules( group ) ).toEqual( [
		{ condition: 'c', operator: '!=', value: 'SPRING' },
		{ condition: 'c', operator: '!=', value: [ 'a' ] },
	] );
} );
