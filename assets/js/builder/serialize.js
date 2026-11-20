/**
 * Reading rules back out of the builder: what Duplicate copies.
 *
 * The form posts the fields themselves; this reads the same fields into the
 * shape a saved rule has, `{ condition, operator, value }`, so a copied group
 * is drawn exactly as a saved one is.
 */

/**
 * A select's value: the chosen values of a multiple one, the value of a
 * single one, null for a single one with nothing to choose.
 *
 * @param {HTMLSelectElement|null} select The select.
 * @return {string|string[]|null} Its value.
 */
export function selectValue( select ) {
	if ( ! select ) {
		return null;
	}

	if ( select.multiple ) {
		return Array.from( select.selectedOptions, ( option ) => option.value );
	}

	return select.selectedIndex < 0 ? null : select.value;
}

/**
 * The value a row holds.
 *
 * @param {Element} row The `.condition-row`.
 * @return {*} `{ number, unit }`, `{ text, unit }`, a list, a string, or null.
 */
export function rowValue( row ) {
	const wrapper = row.querySelector( '.value-field-wrapper' );

	const numberUnit = wrapper.querySelector( '.number-with-unit' );

	if ( numberUnit ) {
		return {
			number: numberUnit.querySelector( '.number-input' ).value,
			unit: selectValue( numberUnit.querySelector( '.unit-select' ) ),
		};
	}

	const textUnit = wrapper.querySelector( '.text-with-unit' );

	if ( textUnit ) {
		return {
			text: textUnit.querySelector( '.text-input' ).value,
			unit: selectValue( textUnit.querySelector( '.unit-select' ) ),
		};
	}

	const multiple = wrapper.querySelector( 'select[multiple]' );

	if ( multiple ) {
		return selectValue( multiple );
	}

	const single = wrapper.querySelector( 'select' );

	if ( single ) {
		return selectValue( single );
	}

	const input = wrapper.querySelector( 'input' );

	return input ? input.value : null;
}

/**
 * A group's rules, as Duplicate copies them: no ids, so the copy gets its own.
 *
 * @param {Element} group The `.condition-group`.
 * @return {Array<{condition: string, operator: *, value: *}>} The rules.
 */
export const groupRules = ( group ) =>
	Array.from( group.querySelectorAll( '.condition-row' ), ( row ) => ( {
		condition: row.querySelector( '.condition-select' ).value,
		operator: selectValue( row.querySelector( '.operator-select' ) ),
		value: rowValue( row ),
	} ) );
