/**
 * What PHP handed the builder.
 *
 * The only module that reads the page's globals. `window.sugarConditions` is
 * keyed by set (Admin\Assets::enqueue_assets(), filtered by
 * `{prefix}_conditions_localized_data`): the builder on the screen says which
 * set it edits, and that set's copy of the library wrote its entry -- two
 * plugins bundling their own copies each read their own. The strings come
 * from PHP rather than `__()` because `{prefix}_conditions_i18n_strings` lets
 * a plugin change them.
 */

/**
 * The builder's configuration.
 *
 * @param {Element|null} builder The `.conditions-builder` element.
 * @return {Object} Its set's entry, or an empty object.
 */
export function readConfig( builder ) {
	const setId = builder ? builder.getAttribute( 'data-set-id' ) || '' : '';

	return ( window.sugarConditions || {} )[ setId ] || {};
}

/**
 * The parts of it the builder uses, every one there even when PHP sent none.
 *
 * @param {Object} raw What readConfig() found.
 * @return {{conditions: Object, operators: Object, restUrl: string, nonce: string, i18n: Object}} The settings.
 */
export function settings( raw ) {
	return {
		conditions: raw.conditions || {},
		operators: raw.operators || {},
		restUrl: raw.restUrl || '',
		nonce: raw.nonce || '',
		i18n: raw.i18n || {},
	};
}
