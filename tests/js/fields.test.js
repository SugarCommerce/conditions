/**
 * The value field each condition type draws: its names (what the save
 * reads), its saved value, and escaping.
 */

import { describe, expect, test } from 'vitest';

import { asList, optionList, valueField } from '../../src/js/builder/fields';

const i18n = { selectValue: 'Select...', selectCondition: 'Pick one' };
const NAME = '_conditions[g1][rules][r1][value]';

/**
 * Draw a field for a condition and return its wrapper.
 *
 * @param {Object} condition The condition.
 * @param {*}      value     The saved value.
 * @return {HTMLElement} A div holding the field.
 */
function draw( condition, value ) {
	const wrapper = document.createElement( 'div' );

	wrapper.innerHTML = valueField(
		{ conditions: { c: condition }, i18n, setId: 'discount_rule' },
		'c',
		'g1',
		'r1',
		value
	);

	return wrapper;
}

const selected = ( select ) =>
	[ ...select.selectedOptions ].map( ( o ) => [ o.value, o.textContent ] );

describe( 'plain inputs', () => {
	test( 'text, with its placeholder', () => {
		const input = draw(
			{ type: 'text', placeholder: 'e.g. SPRING' },
			'SPRING25'
		).firstElementChild;

		expect( input.outerHTML ).toBe(
			`<input type="text" class="value-input" name="${ NAME }" value="SPRING25" placeholder="e.g. SPRING">`
		);
	} );

	test( 'a stored 0 is shown, not emptied', () => {
		expect(
			draw( { type: 'text' }, 0 ).querySelector( 'input' ).value
		).toBe( '0' );
		expect(
			draw( { type: 'number' }, 0 ).querySelector( 'input' ).value
		).toBe( '0' );
		expect(
			draw( { type: 'text' }, null ).querySelector( 'input' ).value
		).toBe( '' );
	} );

	test( 'a type without a field of its own is text', () => {
		expect(
			draw( {}, 'x' ).querySelector( 'input[type="text"].value-input' )
		).not.toBeNull();
		expect(
			draw( { type: 'colour_wheel' }, 'x' ).querySelector(
				'input[type="text"]'
			).name
		).toBe( NAME );
	} );

	test( 'date and time', () => {
		const date = draw( { type: 'date' }, '2026-12-24' ).firstElementChild;
		const time = draw( { type: 'time' }, '09:30' ).firstElementChild;

		expect( [ date.type, date.name, date.value ] ).toEqual( [
			'date',
			NAME,
			'2026-12-24',
		] );
		expect( [ time.type, time.name, time.value ] ).toEqual( [
			'time',
			NAME,
			'09:30',
		] );
	} );

	test( 'a number takes its bounds, and any step unless given one', () => {
		const any = draw( { type: 'number' }, '5' ).firstElementChild;
		const bounded = draw(
			{
				type: 'number',
				min: 0,
				max: 10,
				step: 0.01,
				placeholder: '0.00',
			},
			'5'
		).firstElementChild;

		expect( any.outerHTML ).toBe(
			`<input type="number" class="value-input" name="${ NAME }" value="5" step="any" placeholder="">`
		);
		expect( bounded.outerHTML ).toBe(
			`<input type="number" class="value-input" name="${ NAME }" value="5" min="0" max="10" step="0.01" placeholder="0.00">`
		);
	} );

	test( 'a yes/no condition has no value field', () => {
		expect( draw( { type: 'boolean' }, 'yes' ).innerHTML ).toBe( '' );
	} );

	test( 'no condition is a disabled field asking for one', () => {
		const wrapper = document.createElement( 'div' );

		wrapper.innerHTML = valueField(
			{ conditions: {}, i18n, setId: 's' },
			'gone',
			'g1',
			'r1',
			'x'
		);

		expect( wrapper.innerHTML ).toBe(
			'<input type="text" class="value-input" disabled="" placeholder="Pick one">'
		);
	} );
} );

describe( 'two-part fields', () => {
	const units = [
		{ value: 'g', label: 'grams' },
		{ value: 'kg', label: 'kilograms' },
	];

	test( 'a number and its unit', () => {
		const wrapper = draw(
			{ type: 'number_unit', units, min: 1 },
			{ number: 3, unit: 'kg' }
		);
		const number = wrapper.querySelector(
			'.number-with-unit .number-input'
		);
		const unit = wrapper.querySelector( '.number-with-unit .unit-select' );

		expect( [
			number.name,
			number.value,
			number.min,
			number.step,
		] ).toEqual( [ `${ NAME }[number]`, '3', '1', 'any' ] );
		expect( [ unit.name, unit.value ] ).toEqual( [
			`${ NAME }[unit]`,
			'kg',
		] );
		expect( [ ...unit.options ].map( ( o ) => o.textContent ) ).toEqual( [
			'grams',
			'kilograms',
		] );
	} );

	test( 'a number saved without its unit keeps the number', () => {
		const wrapper = draw( { type: 'number_unit', units }, '7' );

		expect( wrapper.querySelector( '.number-input' ).value ).toBe( '7' );
		expect(
			wrapper.querySelector( '.unit-select option[selected]' )
		).toBeNull();
	} );

	test( 'text and its unit', () => {
		const wrapper = draw(
			{ type: 'text_unit', units, placeholder: 'Name' },
			{ text: 'Alex & "Sam"', unit: 'kg' }
		);
		const text = wrapper.querySelector( '.text-with-unit .text-input' );

		expect( [ text.name, text.value, text.placeholder ] ).toEqual( [
			`${ NAME }[text]`,
			'Alex & "Sam"',
			'Name',
		] );
		expect( wrapper.querySelector( '.unit-select' ).name ).toBe(
			`${ NAME }[unit]`
		);
		expect( wrapper.querySelector( '.unit-select' ).value ).toBe( 'kg' );
	} );

	test( 'text saved as a string keeps it; anything else is empty', () => {
		expect(
			draw( { type: 'text_unit', units }, 'plain' ).querySelector(
				'.text-input'
			).value
		).toBe( 'plain' );
		expect(
			draw( { type: 'text_unit', units }, 5 ).querySelector(
				'.text-input'
			).value
		).toBe( '' );
	} );
} );

describe( 'selects of fixed options', () => {
	test( 'single: an empty option first for the placeholder, the saved one chosen', () => {
		const select = draw(
			{
				type: 'select',
				options: [
					{ value: 'red', label: 'Red' },
					{ value: 'blue', label: 'Blue' },
				],
			},
			'blue'
		).firstElementChild;

		expect( select.className ).toBe( 'value-select conditions-select2' );
		expect( select.name ).toBe( NAME );
		expect( select.multiple ).toBe( false );
		expect( select.getAttribute( 'data-placeholder' ) ).toBe( 'Select...' );
		expect( [ ...select.options ].map( ( o ) => o.value ) ).toEqual( [
			'',
			'red',
			'blue',
		] );
		expect( selected( select ) ).toEqual( [ [ 'blue', 'Blue' ] ] );
	} );

	test( 'multiple: named with [], every saved value chosen', () => {
		const select = draw(
			{
				type: 'select',
				multiple: true,
				placeholder: 'Sizes',
				options: { s: 'Small', m: 'Medium', l: 'Large' },
			},
			[ 's', 'l' ]
		).firstElementChild;

		expect( select.className ).toBe(
			'value-select conditions-select2 multiple'
		);
		expect( select.name ).toBe( `${ NAME }[]` );
		expect( select.multiple ).toBe( true );
		expect( select.getAttribute( 'data-placeholder' ) ).toBe( 'Sizes' );
		expect( select.options[ 0 ].value ).toBe( 's' );
		expect( selected( select ) ).toEqual( [
			[ 's', 'Small' ],
			[ 'l', 'Large' ],
		] );
	} );

	test( 'plain strings are their own value and label', () => {
		const select = draw(
			{ type: 'select', options: [ 'GET', 'POST' ] },
			'POST'
		).firstElementChild;

		expect( selected( select ) ).toEqual( [ [ 'POST', 'POST' ] ] );
	} );

	test( 'a saved value no longer offered stays chosen, after the rest', () => {
		const select = draw(
			{ type: 'select', multiple: true, options: { a: 'A' } },
			[ 'a', 'Netscape', '' ]
		).firstElementChild;

		expect( [ ...select.options ].map( ( o ) => o.value ) ).toEqual( [
			'a',
			'Netscape',
		] );
		expect( selected( select ) ).toEqual( [
			[ 'a', 'A' ],
			[ 'Netscape', 'Netscape' ],
		] );
	} );

	test( 'options that are not a list or a map are none', () => {
		expect( optionList( 'nope' ) ).toEqual( [] );
		expect( optionList( undefined ) ).toEqual( [] );
		expect( optionList( { a: 'A' } ) ).toEqual( [
			{ value: 'a', label: 'A' },
		] );

		const select = draw(
			{ type: 'select', options: 5 },
			'x'
		).firstElementChild;

		expect( [ ...select.options ].map( ( o ) => o.value ) ).toEqual( [
			'',
			'x',
		] );
	} );
} );

describe( 'free-entry selects', () => {
	test.each( [
		[ 'tags', 'conditions-tags-select', 'Type and press Enter…' ],
		[ 'ip', 'conditions-ip-select', 'Enter IP or CIDR, press Enter…' ],
		[
			'email',
			'conditions-email-select',
			'Enter email pattern, press Enter…',
		],
	] )(
		'%s: multiple, the saved entries chosen',
		( type, className, hint ) => {
			const select = draw( { type }, [ 'a', 'b' ] ).firstElementChild;

			expect( select.className ).toBe(
				`value-select ${ className } multiple`
			);
			expect( select.name ).toBe( `${ NAME }[]` );
			expect( select.multiple ).toBe( true );
			expect( select.getAttribute( 'data-placeholder' ) ).toBe( hint );
			expect( selected( select ) ).toEqual( [
				[ 'a', 'a' ],
				[ 'b', 'b' ],
			] );
			expect(
				draw( { type, placeholder: 'Own' }, 'one' ).firstElementChild
			).toMatchObject( { dataset: { placeholder: 'Own' } } );
			expect(
				selected( draw( { type }, 'one' ).firstElementChild )
			).toEqual( [ [ 'one', 'one' ] ] );
		}
	);
} );

describe( 'searched selects', () => {
	test( 'a post picker names its post type', () => {
		const select = draw(
			{ type: 'post', post_type: 'page', multiple: true },
			[ 4, 9 ]
		).firstElementChild;

		expect( select.className ).toBe(
			'value-select conditions-ajax-select multiple'
		);
		expect( select.name ).toBe( `${ NAME }[]` );
		expect( select.dataset ).toMatchObject( {
			type: 'post',
			postType: 'page',
			placeholder: 'Select...',
		} );
		expect( select.dataset.taxonomy ).toBeUndefined();
		expect( selected( select ) ).toEqual( [
			[ '4', '4' ],
			[ '9', '9' ],
		] );
	} );

	test( 'a term picker its taxonomy, a user picker its roles', () => {
		const term = draw(
			{ type: 'term', taxonomy: 'category', placeholder: 'Category' },
			'3'
		).firstElementChild;
		const user = draw(
			{ type: 'user', role: [ 'administrator', 'editor' ] },
			[]
		).firstElementChild;
		const one = draw(
			{ type: 'user', role: 'shop_manager' },
			''
		).firstElementChild;

		expect( term.name ).toBe( NAME );
		expect( term.multiple ).toBe( false );
		expect( term.dataset ).toMatchObject( {
			type: 'term',
			taxonomy: 'category',
			placeholder: 'Category',
		} );
		expect( user.dataset.role ).toBe( 'administrator,editor' );
		expect( one.dataset.role ).toBe( 'shop_manager' );
		expect( user.options ).toHaveLength( 0 );
	} );

	test( 'a post picker without a post type, or a type that does not take one, names none', () => {
		expect(
			draw( { type: 'post' }, '' ).firstElementChild.hasAttribute(
				'data-post-type'
			)
		).toBe( false );
		expect(
			draw(
				{ type: 'term', post_type: 'page' },
				''
			).firstElementChild.hasAttribute( 'data-post-type' )
		).toBe( false );
		expect(
			draw( { type: 'post', role: 'x', taxonomy: 'y' }, '' )
				.firstElementChild.attributes.length
		).toBe( 4 );
	} );

	test( "a condition's own search names its set and itself", () => {
		const select = draw( { type: 'ajax', multiple: true }, [
			'a@b.c',
		] ).firstElementChild;

		expect( select.className ).toBe(
			'value-select conditions-custom-ajax-select multiple'
		);
		expect( select.dataset ).toMatchObject( {
			type: 'ajax',
			setId: 'discount_rule',
			conditionId: 'c',
			placeholder: 'Select...',
		} );
		expect( selected( select ) ).toEqual( [ [ 'a@b.c', 'a@b.c' ] ] );
	} );
} );

test( 'every value and attribute is escaped', () => {
	const attack = '" onfocus="alert(1)" x="<b>';
	const wrapper = draw(
		{
			type: 'select',
			multiple: true,
			placeholder: attack,
			options: [ { value: attack, label: attack } ],
		},
		[ attack, '<img src=x onerror=alert(1)>' ]
	);
	const text = draw( { type: 'text', placeholder: attack }, attack );

	expect( wrapper.querySelector( '[onfocus], img, b' ) ).toBeNull();
	expect( text.querySelector( '[onfocus]' ) ).toBeNull();
	expect( text.firstElementChild.value ).toBe( attack );
	expect( selected( wrapper.firstElementChild ) ).toEqual( [
		[ attack, attack ],
		[ '<img src=x onerror=alert(1)>', '<img src=x onerror=alert(1)>' ],
	] );
} );

test( 'a saved value is a list: as it is, one as one, nothing as none', () => {
	expect( asList( [ 'a' ] ) ).toEqual( [ 'a' ] );
	expect( asList( 'a' ) ).toEqual( [ 'a' ] );
	expect( asList( '' ) ).toEqual( [] );
	expect( asList( null ) ).toEqual( [] );
	expect( asList( 0 ) ).toEqual( [] );
} );
