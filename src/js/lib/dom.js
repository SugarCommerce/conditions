/**
 * The few DOM helpers the builder needs, so none of it needs jQuery.
 */

/**
 * Every element matching a selector, as an array.
 *
 * @param {string}           selector CSS selector.
 * @param {Document|Element} root     Where to look.
 * @return {Element[]} The elements.
 */
export const all = ( selector, root = document ) =>
	Array.from( root.querySelectorAll( selector ) );

/**
 * Listen for an event on whatever matches a selector, now or later.
 *
 * @param {Document|Element} root     Where to listen.
 * @param {string}           type     Event type.
 * @param {string}           selector What the event must come from.
 * @param {Function}         handler  Called with ( event, matched element ).
 * @return {Function} Stops listening.
 */
export function on( root, type, selector, handler ) {
	const listener = ( event ) => {
		const target =
			event.target instanceof window.Element
				? event.target.closest( selector )
				: null;

		if ( target && root.contains( target ) ) {
			handler( event, target );
		}
	};

	root.addEventListener( type, listener );

	return () => root.removeEventListener( type, listener );
}

/**
 * Escape a value for HTML text or a quoted attribute.
 *
 * Quotes too: text-node escaping leaves them alone, and every value here goes
 * inside a double-quoted attribute, where a stored `" onfocus="alert(1)` came
 * back as a live attribute on the next load.
 *
 * @param {*} value Anything.
 * @return {string} Escaped text.
 */
export function escapeHtml( value ) {
	return String( value ?? '' )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' )
		.replace( /'/g, '&#39;' );
}

/**
 * Markup that is already safe, for `html` to insert as it is.
 */
class Raw {
	constructor( markup ) {
		this.markup = String( markup ?? '' );
	}

	toString() {
		return this.markup;
	}
}

/**
 * Mark markup as safe to insert unescaped: the output of another `html` call.
 *
 * @param {string} markup Markup.
 * @return {Raw} Wrapped markup.
 */
export const raw = ( markup ) => new Raw( markup );

/**
 * A template literal tag that escapes every value put into it.
 *
 * Escaping is the default rather than a decision made at each value, which
 * is how one gets missed: arrays are joined, `raw()` values and nested
 * `html` results pass through, `false`, `null` and `undefined` are nothing.
 *
 * @param {string[]} strings The literal parts.
 * @param {...*}     values  The values.
 * @return {Raw} Safe markup.
 */
export function html( strings, ...values ) {
	const render = ( value ) => {
		if ( value instanceof Raw ) {
			return value.markup;
		}

		if ( Array.isArray( value ) ) {
			return value.map( render ).join( '' );
		}

		if ( value === false || value === null || value === undefined ) {
			return '';
		}

		return escapeHtml( value );
	};

	return new Raw(
		strings.reduce(
			( out, part, index ) =>
				out +
				part +
				( index < values.length ? render( values[ index ] ) : '' ),
			''
		)
	);
}
