/**
 * The one module that reaches Select2, and through it jQuery.
 *
 * The builder's searchable selects are Select2 -- a jQuery plugin with no
 * other way in -- which this library bundles and PHP loads before the
 * builder (handle `{prefix}-conditions-select2`). Everything Select2-shaped is
 * here: the rest of the builder describes a select (placeholder, free entry,
 * a search function) in plain terms and this turns it into Select2's options,
 * so another picker can replace it by replacing this file. Read from the page
 * when wanted, never imported; without Select2 the selects stay native ones.
 */

/**
 * The page's jQuery, when it has Select2.
 *
 * @return {Function|null} jQuery, or null.
 */
function select2() {
	const $ = window.jQuery;

	return $ && $.fn && $.fn.select2 ? $ : null;
}

/**
 * Whether the page has Select2.
 *
 * @return {boolean} Whether it does.
 */
export const available = () => !! select2();

/**
 * Whether a select has been made a Select2 already.
 *
 * @param {Element} select The select.
 * @return {boolean} Whether it has.
 */
export const isEnhanced = ( select ) =>
	select.classList.contains( 'select2-hidden-accessible' );

/**
 * Turn off the browser's autocomplete and spellcheck in the open dropdown's
 * search field: suggestions there cover the results.
 */
function quietSearch() {
	document
		.querySelectorAll( '.select2-container--open .select2-search__field' )
		.forEach( ( field ) => {
			field.setAttribute( 'autocomplete', 'off' );
			field.setAttribute( 'autocorrect', 'off' );
			field.setAttribute( 'autocapitalize', 'off' );
			field.setAttribute( 'spellcheck', 'false' );
		} );
}

/**
 * Select2's `ajax` option for a search function.
 *
 * Select2 calls `transport` after its own delay, aborts the request before a
 * newer one, and shows its "could not be loaded" message on failure -- unless
 * the request reports status 0, which is how it knows one was cancelled.
 *
 * @param {Function} find ( term ) => { promise, abort, aborted }.
 * @return {Object} The option.
 */
const ajax = ( find ) => ( {
	delay: 250,
	data: ( params ) => ( { term: params.term } ),
	transport( options, success, failure ) {
		const request = find( options.data.term );
		const handle = {
			abort: request.abort,
			get status() {
				return request.aborted() ? 0 : 500;
			},
		};

		request.promise.then(
			( results ) => success( { results } ),
			() => failure()
		);

		return handle;
	},
	processResults: ( data ) => data,
} );

/**
 * Make a select searchable.
 *
 * @param {HTMLSelectElement} select                   The select.
 * @param {Object}            options                  What it should do.
 * @param {string}            options.placeholder      Shown when nothing is chosen.
 * @param {boolean}           options.allowClear       Whether it has a clear button.
 * @param {Function}          [options.labelSelection] ( { id, text, element } ) => what the chosen item reads.
 * @param {Object}            [options.freeEntry]      `{ separators, create( term ) }` to let entries be typed.
 * @param {Function}          [options.search]         ( term ) => { promise, abort, aborted } to search a route.
 * @return {boolean} False when there is no Select2 or it already is one.
 */
export function enhance( select, options ) {
	const $ = select2();

	if ( ! $ || isEnhanced( select ) ) {
		return false;
	}

	const settings = {
		width: '100%',
		allowClear: options.allowClear,
		placeholder: options.placeholder,
	};

	if ( options.labelSelection ) {
		settings.templateSelection = options.labelSelection;
	}

	if ( options.freeEntry ) {
		settings.tags = true;
		settings.tokenSeparators = options.freeEntry.separators;
		settings.createTag = ( params ) =>
			options.freeEntry.create( params.term );
	}

	if ( options.search ) {
		settings.ajax = ajax( options.search );
	}

	$( select ).select2( settings );
	$( select ).on( 'select2:open', quietSearch );

	return true;
}

/**
 * Show a select's current options and value again, after they were changed
 * from outside Select2.
 *
 * @param {HTMLSelectElement} select The select.
 */
export function refresh( select ) {
	const $ = select2();

	if ( $ && isEnhanced( select ) ) {
		$( select ).trigger( 'change.select2' );
	}
}

/**
 * Undo every Select2 inside an element, before it is replaced or removed.
 *
 * @param {Element} root The element.
 */
export function destroyWithin( root ) {
	const $ = select2();

	if ( $ ) {
		root.querySelectorAll( '.select2-hidden-accessible' ).forEach(
			( select ) => $( select ).select2( 'destroy' )
		);
	}
}

/**
 * Listen for `change` on whatever matches a selector inside an element.
 *
 * Select2 announces a choice with jQuery's trigger(), which runs jQuery's
 * handlers and never the browser's, so a plain listener would not hear it.
 * With jQuery on the page the handler is bound through it, and hears both
 * that and a native change exactly once; without, it is a plain listener.
 *
 * @param {Element}  root     Where to listen.
 * @param {string}   selector What the change must come from.
 * @param {Function} handler  Called with ( event, matched element ).
 * @param {Function} fallback ( root, type, selector, handler ) for a page without jQuery.
 */
export function onChange( root, selector, handler, fallback ) {
	const $ = window.jQuery;

	if ( ! $ ) {
		fallback( root, 'change', selector, handler );
		return;
	}

	$( root ).on( 'change', selector, function ( event ) {
		handler( event, this );
	} );
}
