/**
 * Which picker each of the builder's selects becomes.
 *
 * The condition select reads "Group → Label" once chosen. A value select is
 * searchable (`conditions-select2`), takes typed entries (tags, IPs, email
 * patterns), or searches a REST route and labels its saved values
 * (`conditions-ajax-select`, `conditions-custom-ajax-select`).
 */

import { enhance, isEnhanced, refresh } from '../lib/select2';
import { createEntry, modeOf } from './tags';
import { label, search, sourceOf } from './search';

/**
 * The value selects that become pickers.
 */
export const VALUE_SELECTS =
	'.conditions-select2, .conditions-ajax-select, .conditions-custom-ajax-select, .conditions-tags-select, .conditions-ip-select, .conditions-email-select';

/**
 * What a chosen condition reads: its group, an arrow, its label.
 *
 * @param {{id: string, text: string, element: Element}} option The chosen option.
 * @return {string} "Cart → Cart Total", or the label when it has no group.
 */
export function conditionLabel( option ) {
	if ( ! option.id ) {
		return option.text;
	}

	const group = option.element ? option.element.closest( 'optgroup' ) : null;
	const name = group ? group.getAttribute( 'label' ) : '';

	return name ? `${ name } → ${ option.text }` : option.text;
}

/**
 * Make a row's condition select searchable.
 *
 * @param {HTMLSelectElement} select The `.condition-select`.
 * @param {Object}            i18n   The builder's strings.
 */
export function enhanceCondition( select, i18n ) {
	enhance( select, {
		allowClear: false,
		placeholder: i18n.selectCondition || 'Select condition...',
		labelSelection: conditionLabel,
	} );
}

/**
 * Make a value select the picker it is marked as.
 *
 * @param {HTMLSelectElement} select  The value select.
 * @param {Object}            context `{ i18n, restUrl, nonce, fetcher }`.
 * @return {Promise<boolean>|null} The saved values being labelled, for a select that searches.
 */
export function enhanceValue( select, context ) {
	if ( isEnhanced( select ) ) {
		return null;
	}

	const options = {
		allowClear: true,
		placeholder: select.dataset.placeholder || context.i18n.selectValue,
	};

	const mode = modeOf( select );

	if ( mode ) {
		enhance( select, {
			...options,
			freeEntry: {
				separators: mode.separators,
				create: ( term ) => createEntry( term, mode.accepts ),
			},
		} );

		return null;
	}

	const source = sourceOf( select, context.restUrl );

	if ( ! source ) {
		enhance( select, options );

		return null;
	}

	enhance( select, {
		...options,
		search: ( term ) =>
			search( context.fetcher, source, term, context.nonce ),
	} );

	return label( context.fetcher, select, source, context.nonce, refresh );
}
