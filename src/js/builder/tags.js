/**
 * The three free-entry value fields: tags, IP addresses and email patterns.
 *
 * Each is a multiple select the admin types entries into. What separates one
 * entry from the next, and what is accepted as one, is decided here; the
 * server checks again when the rule is saved.
 */

/**
 * Whether a term looks like an IP address, a CIDR range or a wildcard:
 * `192.168.1.1`, `192.168.1.0/24`, `10.*`, `2001:db8::1`.
 *
 * @param {string} term A trimmed term.
 * @return {boolean} Whether it may be added.
 */
export const isIpPattern = ( term ) => /^[\d.:\/*a-fA-F]+$/.test( term );

/**
 * Whether a term looks like an email pattern: `user@domain.com`,
 * `@domain.com`, `.edu`, `domain.com`.
 *
 * @param {string} term A trimmed term.
 * @return {boolean} Whether it may be added.
 */
export const isEmailPattern = ( term ) => /^[@.]?[\w.\-@]+$/.test( term );

/**
 * The modes, by the class PHP's markup (fields.js) gives the select.
 *
 * Tags and IPs split on a comma or a space. Email patterns split only on a
 * comma: a space is valid in some contexts.
 */
export const FREE_ENTRY = {
	'conditions-tags-select': {
		separators: [ ',', ' ' ],
		accepts: () => true,
	},
	'conditions-ip-select': {
		separators: [ ',', ' ' ],
		accepts: isIpPattern,
	},
	'conditions-email-select': {
		separators: [ ',' ],
		accepts: isEmailPattern,
	},
};

/**
 * The free-entry mode of a select.
 *
 * @param {Element} select The value select.
 * @return {Object|null} Its mode, or null when entries cannot be typed.
 */
export function modeOf( select ) {
	const name = Object.keys( FREE_ENTRY ).find( ( className ) =>
		select.classList.contains( className )
	);

	return name ? FREE_ENTRY[ name ] : null;
}

/**
 * Turn what was typed into an entry, or refuse it.
 *
 * @param {*}        term    What was typed.
 * @param {Function} accepts The mode's test.
 * @return {{id: string, text: string, newTag: boolean}|null} The entry, or null.
 */
export function createEntry( term, accepts ) {
	const value = String( term ?? '' ).trim();

	if ( value === '' || ! accepts( value ) ) {
		return null;
	}

	return { id: value, text: value, newTag: true };
}
