/**
 * The rule builder in a set's meta box (Admin\Editor): its buttons, its
 * condition selects, and the groups a saved rule already has.
 */

import { belongsTo, build, readConfig, settings } from '../lib/config';
import { on } from '../lib/dom';
import { onChange } from '../lib/select2';
import { applyCondition } from './rule';
import {
	addGroup,
	addRule,
	deleteGroup,
	duplicateGroup,
	removeRule,
} from './rows';

/**
 * The groups the rule was saved with, from `.condition-groups`' data.
 *
 * @param {Element} container The `.condition-groups`.
 * @return {Array} The groups; none when there are none or the data is not a list.
 */
export function savedGroups( container ) {
	try {
		const groups = JSON.parse(
			container.getAttribute( 'data-conditions' ) || '[]'
		);

		return Array.isArray( groups ) ? groups : [];
	} catch {
		return [];
	}
}

/**
 * Start this build's builders on the page, if it has any.
 *
 * Only the builders whose `data-build` names this build: another plugin's
 * copy of the library, loaded on the same page, starts its own.
 *
 * @param {Function} fetcher apiFetch, or a fake: what the searched fields ask with.
 * @param {string}   handle  The build's handle. This script's own by default.
 * @return {Object[]} The builders started, each as start() returns it.
 */
export function init( fetcher, handle = build ) {
	return Array.from( document.querySelectorAll( '.conditions-builder' ) )
		.filter( ( builder ) => belongsTo( builder, handle ) )
		.map( ( builder ) => start( builder, fetcher, handle ) );
}

/**
 * Start one builder: its buttons and condition selects, then the groups the
 * rule was saved with, or one empty group.
 *
 * @param {Element}  builder The `.conditions-builder`.
 * @param {Function} fetcher What the searched fields ask with.
 * @param {string}   handle  The build's handle, whose entry it reads.
 * @return {Object} The builder: its settings, its elements, and `settled()`,
 *                  which resolves once every saved value has been labelled.
 */
export function start( builder, fetcher, handle ) {
	const context = {
		...settings( readConfig( builder, handle ) ),
		builder,
		container: builder.querySelector( '.condition-groups' ),
		setId: builder.getAttribute( 'data-set-id' ) || '',
		fetcher,
		pending: [],
		settled: () => Promise.all( context.pending ),
	};

	const click = ( selector, handler ) =>
		on( builder, 'click', selector, ( event, target ) => {
			event.preventDefault();
			handler( target );
		} );

	click( '.add-group', () => addGroup( context ) );

	click( '.conditions-list-footer .add-condition', ( button ) => {
		const group = button.closest( '.condition-group' );

		addRule( context, group, group.getAttribute( 'data-group-id' ) );
	} );

	click( '.delete-group', ( link ) =>
		deleteGroup( context, link.closest( '.condition-group' ) )
	);

	click( '.duplicate-group', ( link ) =>
		duplicateGroup( context, link.closest( '.condition-group' ) )
	);

	click( '.remove-condition', removeRule );

	onChange(
		builder,
		'.condition-select',
		( event, select ) =>
			context.pending.push(
				applyCondition( context, select.closest( '.condition-row' ) )
			),
		on
	);

	const groups = savedGroups( context.container );

	if ( groups.length ) {
		groups.forEach( ( group ) => addGroup( context, group ) );
	} else {
		addGroup( context );
	}

	return context;
}
