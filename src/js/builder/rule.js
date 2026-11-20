/**
 * A rule row following its condition: the operators it offers, the value
 * field its type takes, and what its tooltip says.
 */

import { __ } from '@wordpress/i18n';

import { destroyWithin } from '../lib/select2';
import { valueField } from './fields';
import { fillOperators, operatorsFor } from './operators';
import { enhanceValue, VALUE_SELECTS } from './selects';

/**
 * Draw a row's value field for a condition, and make its selects pickers.
 *
 * @param {Object}  context     The builder.
 * @param {Element} row         The `.condition-row`.
 * @param {string}  conditionId The chosen condition.
 * @param {string}  groupId     The row's group.
 * @param {string}  ruleId      The row's rule.
 * @param {*}       value       The saved value, if any.
 * @return {Promise} Settles once any saved values a picker searches for are labelled.
 */
export function showValueField(
	context,
	row,
	conditionId,
	groupId,
	ruleId,
	value
) {
	const wrapper = row.querySelector( '.value-field-wrapper' );

	destroyWithin( wrapper );
	wrapper.innerHTML = valueField(
		context,
		conditionId,
		groupId,
		ruleId,
		value
	);

	return Promise.all(
		Array.from( wrapper.querySelectorAll( VALUE_SELECTS ), ( select ) =>
			enhanceValue( select, context )
		)
	);
}

/**
 * Say what the condition matches in the row's tooltip.
 *
 * The icon always shows: with no condition chosen it says to choose one.
 * Hiding it until then made the row jump when one was chosen.
 *
 * @param {Element}          row       The `.condition-row`.
 * @param {Object|undefined} condition The chosen condition.
 */
export function updateTooltip( row, condition ) {
	const tooltip = row.querySelector( '.condition-tooltip' );

	tooltip.setAttribute(
		'data-tip',
		condition && condition.description
			? condition.description
			: tooltip.getAttribute( 'data-placeholder' ) ||
					__(
						'Select a condition to see what it matches.',
						'sugarcommerce'
					)
	);
}

/**
 * Bring a row in line with its condition select, keeping a saved rule's
 * operator and value.
 *
 * @param {Object}  context The builder.
 * @param {Element} row     The `.condition-row`.
 * @param {Object}  [saved] The saved rule: `{ operator, value }`.
 * @return {Promise} showValueField()'s.
 */
export function applyCondition( context, row, saved ) {
	const conditionId = row.querySelector( '.condition-select' ).value;
	const groupId = row
		.closest( '.condition-group' )
		.getAttribute( 'data-group-id' );
	const ruleId = row.getAttribute( 'data-condition-id' );
	const condition = context.conditions[ conditionId ];

	fillOperators(
		row.querySelector( '.operator-select' ),
		conditionId,
		operatorsFor( condition, context.operators ),
		saved?.operator
	);
	updateTooltip( row, condition );

	return showValueField(
		context,
		row,
		conditionId,
		groupId,
		ruleId,
		saved?.value
	);
}
