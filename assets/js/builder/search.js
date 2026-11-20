/**
 * The searched value fields' requests: what they ask the library's REST
 * routes, and what comes back.
 *
 * Posts, terms and users are searched at `{restUrl}/posts`, `/terms` and
 * `/users` with the field's post type, taxonomy or role; a condition with its
 * own `ajax` callback at `{restUrl}/ajax` with its set and condition. The
 * same routes, given `include`, label the values a saved rule already holds
 * -- which is how a reloaded rule shows titles rather than ids.
 *
 * The network is an argument (`fetcher`, apiFetch's shape), so the requests
 * can be checked without one.
 */

/**
 * A query string from the parameters that have a value. An empty string is a
 * value (`search=` asks for everything); undefined and null are not sent.
 *
 * @param {Object} params Name => value, in the order to send them.
 * @return {string} `?a=1&b=2`, or empty.
 */
export function queryString( params ) {
	const pairs = Object.entries( params )
		.filter( ( [ , value ] ) => value !== undefined && value !== null )
		.map(
			( [ key, value ] ) =>
				`${ encodeURIComponent( key ) }=${ encodeURIComponent( value ) }`
		);

	return pairs.length ? '?' + pairs.join( '&' ) : '';
}

/**
 * Where a searched select asks, and what it asks with.
 *
 * @param {HTMLSelectElement} select  The value select.
 * @param {string}            restUrl The library's REST namespace URL.
 * @return {{url: string, params: Object}|null} Null for a select that does not search.
 */
export function sourceOf( select, restUrl ) {
	const data = select.dataset;

	if ( select.classList.contains( 'conditions-ajax-select' ) ) {
		return {
			url: `${ restUrl }/${ data.type }s`,
			params: {
				post_type: data.type === 'post' ? data.postType : undefined,
				taxonomy: data.type === 'term' ? data.taxonomy : undefined,
				role: data.type === 'user' ? data.role : undefined,
			},
			// Labelling sends all three, whichever apply.
			labelParams: {
				post_type: data.postType,
				taxonomy: data.taxonomy,
				role: data.role,
			},
		};
	}

	if ( select.classList.contains( 'conditions-custom-ajax-select' ) ) {
		const params = {
			set_id: data.setId,
			condition_id: data.conditionId,
		};

		return {
			url: `${ restUrl }/ajax`,
			params,
			labelParams: params,
			custom: true,
		};
	}

	return null;
}

/**
 * The URL that searches for a term.
 *
 * @param {Object}           source sourceOf()'s answer.
 * @param {string|undefined} term   What was typed; undefined before anything was.
 * @return {string} The URL.
 */
export function searchUrl( source, term ) {
	// A custom callback is asked with its set and condition first; the
	// built-in routes with the term first.
	const params = source.custom
		? { ...source.params, search: term }
		: { search: term, ...source.params };

	return source.url + queryString( params );
}

/**
 * The URL that labels values already chosen.
 *
 * @param {Object}   source sourceOf()'s answer.
 * @param {string[]} ids    The values.
 * @return {string} The URL.
 */
export function labelUrl( source, ids ) {
	const params = source.custom
		? { ...source.labelParams, include: ids.join( ',' ) }
		: { include: ids.join( ',' ), ...source.labelParams };

	return source.url + queryString( params );
}

/**
 * The routes' `{ value, label }` items as the select's results.
 *
 * @param {Array} items What the route answered.
 * @return {Array<{id: *, text: *}>} The results.
 */
export const toResults = ( items ) =>
	( Array.isArray( items ) ? items : [] ).map( ( item ) => ( {
		id: item.value,
		text: item.label,
	} ) );

/**
 * Ask a route, with the REST nonce.
 *
 * @param {Function} fetcher apiFetch, or a fake.
 * @param {string}   url     The URL.
 * @param {string}   nonce   The REST nonce.
 * @param {Object}   signal  An AbortSignal, if the request may be cancelled.
 * @return {Promise<Array>} The items.
 */
export const request = ( fetcher, url, nonce, signal ) =>
	fetcher( {
		url,
		method: 'GET',
		headers: nonce ? { 'X-WP-Nonce': nonce } : {},
		...( signal ? { signal } : {} ),
	} );

/**
 * Search for a term: a request that can be cancelled when the next keystroke
 * replaces it.
 *
 * @param {Function}         fetcher apiFetch, or a fake.
 * @param {Object}           source  sourceOf()'s answer.
 * @param {string|undefined} term    What was typed.
 * @param {string}           nonce   The REST nonce.
 * @return {{promise: Promise<Array>, abort: Function, aborted: Function}} The request.
 */
export function search( fetcher, source, term, nonce ) {
	const controller =
		typeof window.AbortController === 'function'
			? new window.AbortController()
			: null;

	return {
		promise: request(
			fetcher,
			searchUrl( source, term ),
			nonce,
			controller ? controller.signal : null
		).then( toResults ),
		abort: () => controller && controller.abort(),
		aborted: () => !! ( controller && controller.signal.aborted ),
	};
}

/**
 * Label the values a saved rule holds: the select's options replaced by the
 * route's, every one chosen, in the order the route gives.
 *
 * Nothing changes when there is nothing to label or the request fails: the
 * values stay, shown as they are, and are saved as they were.
 *
 * @param {Function}          fetcher   apiFetch, or a fake.
 * @param {HTMLSelectElement} select    The value select.
 * @param {Object}            source    sourceOf()'s answer.
 * @param {string}            nonce     The REST nonce.
 * @param {Function}          refreshed Called once the options are replaced.
 * @return {Promise<boolean>} Whether the options were replaced.
 */
export async function label( fetcher, select, source, nonce, refreshed ) {
	const ids = Array.from(
		select.selectedOptions,
		( option ) => option.value
	);

	if ( ! ids.length ) {
		return false;
	}

	let items;

	try {
		items = await request( fetcher, labelUrl( source, ids ), nonce );
	} catch {
		return false;
	}

	select.replaceChildren(
		...( Array.isArray( items ) ? items : [] ).map(
			( item ) => new window.Option( item.label, item.value, true, true )
		)
	);
	refreshed( select );

	return true;
}
