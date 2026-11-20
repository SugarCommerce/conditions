/**
 * Adding and removing groups and rule rows.
 *
 * Groups are ORed, the rows inside a group ANDed. There is always at least
 * one group, and a group's last row cannot be removed -- delete the group.
 */

import { __ } from '@wordpress/i18n';

import { destroyWithin, refresh } from '../lib/select2';
import { applyCondition } from './rule';
import { enhanceCondition } from './selects';
import { groupRules } from './serialize';
import { connectorElement, groupElement, rowElement } from './templates';

/**
 * A new id for a group or a rule: nine characters of base 36.
 *
 * @return {string} The id.
 */
export const generateId = () => Math.random().toString( 36 ).substring( 2, 11 );

/**
 * Let a group's rows be removed only while it has more than one.
 *
 * @param {Element} group The `.condition-group`.
 */
export function updateRemoveButtons( group ) {
	const single = group.querySelectorAll( '.condition-row' ).length <= 1;

	group.querySelectorAll( '.remove-condition' ).forEach( ( button ) => {
		button.classList.toggle( 'disabled', single );
		button.disabled = single;
	} );
}

/**
 * Add a rule row to a group, empty or as saved.
 *
 * @param {Object}  context The builder.
 * @param {Element} group   The `.condition-group`.
 * @param {string}  groupId Its id.
 * @param {Object}  [saved] The saved rule: `{ id, condition, operator, value }`.
 * @return {Element} The row.
 */
export function addRule( context, group, groupId, saved ) {
	const row = rowElement(
		context.builder,
		saved?.id || generateId(),
		groupId
	);

	group.querySelector( '.conditions-list' ).append( row );

	const select = row.querySelector( '.condition-select' );

	enhanceCondition( select, context.i18n );

	if ( saved?.condition ) {
		select.value = saved.condition;
		refresh( select );
		context.pending.push( applyCondition( context, row, saved ) );
	}

	updateRemoveButtons( group );

	return row;
}

/**
 * Add a group, with an OR before it when it is not the first.
 *
 * @param {Object} context The builder.
 * @param {Object} [saved] The saved group: `{ id, rules }`. None for an empty one.
 * @return {Element} The group.
 */
export function addGroup( context, saved ) {
	const container = context.container;
	const groupId = saved?.id || generateId();
	const index = container.querySelectorAll( '.condition-group' ).length;

	if ( index > 0 ) {
		container.append( connectorElement( context.builder ) );
	}

	const group = groupElement( context.builder, groupId, index );

	container.append( group );

	const rules = saved?.rules || [];

	if ( rules.length === 0 ) {
		addRule( context, group, groupId );
	} else {
		rules.forEach( ( rule ) => addRule( context, group, groupId, rule ) );
	}

	updateRemoveButtons( group );

	return group;
}

/**
 * Add a copy of a group's rules, as they stand, as a new group.
 *
 * @param {Object}  context The builder.
 * @param {Element} group   The `.condition-group` to copy.
 * @return {Element} The copy.
 */
export const duplicateGroup = ( context, group ) =>
	addGroup( context, { rules: groupRules( group ) } );

/**
 * Delete a group and the OR that joined it to the others.
 *
 * The OR before it goes; the first group has none, so the one after it goes.
 * An empty group takes the last one's place, and whichever group is now first
 * reads "Match all of the following rules".
 *
 * @param {Object}  context The builder.
 * @param {Element} group   The `.condition-group`.
 */
export function deleteGroup( context, group ) {
	const container = group.parentElement;
	const before = group.previousElementSibling;
	const after = group.nextElementSibling;

	if ( before && before.classList.contains( 'group-connector' ) ) {
		before.remove();
	} else if ( after && after.classList.contains( 'group-connector' ) ) {
		after.remove();
	}

	destroyWithin( group );
	group.remove();

	if ( ! container.querySelector( '.condition-group' ) ) {
		addGroup( context );
	}

	container.querySelector( '.condition-group .group-label' ).textContent =
		context.i18n.matchAll ||
		__( 'Match all of the following rules', 'sugarcommerce' );
}

/**
 * Remove a rule row, unless it is its group's only one.
 *
 * @param {Element} button The row's `.remove-condition`.
 * @return {boolean} Whether it was removed.
 */
export function removeRule( button ) {
	if ( button.disabled || button.classList.contains( 'disabled' ) ) {
		return false;
	}

	const row = button.closest( '.condition-row' );
	const group = row.closest( '.condition-group' );

	destroyWithin( row );
	row.remove();
	updateRemoveButtons( group );

	return true;
}
