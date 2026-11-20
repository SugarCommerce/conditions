/**
 * What PHP handed the builder.
 *
 * The only module that reads the page's globals. Each build of this library
 * -- a plain Composer install, or a Strauss-prefixed copy inside another
 * plugin -- publishes its sets' data into `window.SugarCommerceConditions`,
 * keyed by its builder script's handle and then by set
 * (Admin\Assets::enqueue_assets(), filtered by
 * `{prefix}_conditions_localized_data`). A set's id is the plugin's choice,
 * so two plugins could pick the same one; the handle is the build's own.
 *
 * A script finds its handle from the id WordPress stamps on its <script>
 * element, "{handle}-js", and starts only the builders whose `data-build`
 * names it (Admin\Editor). The strings come from PHP rather than `__()`
 * because `{prefix}_conditions_i18n_strings` lets a plugin change them.
 */

// Read while the script runs. Once it has, document.currentScript is null.
const script = document.currentScript;

/**
 * The handle a script element names.
 *
 * @param {Element|null} element The <script> element, or null.
 * @return {string} The handle, or '' when the element has no id.
 */
export function handleOf( element ) {
	return element && element.id ? element.id.replace( /-js$/, '' ) : '';
}

/**
 * This script's handle: the build it belongs to.
 */
export const build = handleOf( script );

/**
 * Whether a builder belongs to a build.
 *
 * @param {Element} builder The `.conditions-builder` element.
 * @param {string}  handle  The build's handle.
 * @return {boolean} Whether its `data-build` names the build.
 */
export function belongsTo( builder, handle ) {
	return '' !== handle && builder.getAttribute( 'data-build' ) === handle;
}

/**
 * The builder's configuration, from its build's entry.
 *
 * @param {Element|null} builder The `.conditions-builder` element.
 * @param {string}       handle  The build's handle.
 * @return {Object} Its set's entry, or an empty object.
 */
export function readConfig( builder, handle ) {
	const setId = builder ? builder.getAttribute( 'data-set-id' ) || '' : '';
	const entry = ( window.SugarCommerceConditions || {} )[ handle ] || {};

	return entry[ setId ] || {};
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
