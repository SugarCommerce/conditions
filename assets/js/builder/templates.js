/**
 * A group and a rule row, from the templates PHP drew (Admin\Editor).
 *
 * The markup and its strings stay in PHP, translated there; the ids and names
 * that differ from one copy to the next are put in here.
 */

/**
 * A fresh copy of a template's element.
 *
 * @param {string} id The template's id.
 * @return {Element|null} The copy, or null when the page has no such template.
 */
function copy( id ) {
	const template = document.getElementById( id );

	if ( ! template || ! template.content ) {
		return null;
	}

	return template.content.firstElementChild.cloneNode( true );
}

/**
 * A group: "Match all of the following rules" when it is the first, "Or match
 * all of the following rules" after.
 *
 * @param {string} id    The group's id.
 * @param {number} index How many groups come before it.
 * @return {Element|null} The group.
 */
export function groupElement( id, index ) {
	const group = copy( 'tmpl-condition-group' );

	if ( ! group ) {
		return null;
	}

	const template = document.getElementById( 'tmpl-condition-group' );

	group.setAttribute( 'data-group-id', id );
	group.querySelector( '.group-label' ).textContent =
		index === 0
			? template.getAttribute( 'data-first-label' )
			: template.getAttribute( 'data-other-label' );

	return group;
}

/**
 * The OR between two groups.
 *
 * @return {Element|null} The connector.
 */
export const connectorElement = () => copy( 'tmpl-group-connector' );

/**
 * A rule row, its condition and operator selects named for the form.
 *
 * @param {string} id      The rule's id.
 * @param {string} groupId Its group's id.
 * @return {Element|null} The row.
 */
export function rowElement( id, groupId ) {
	const row = copy( 'tmpl-condition-row' );

	if ( ! row ) {
		return null;
	}

	const base = `_conditions[${ groupId }][rules][${ id }]`;

	row.setAttribute( 'data-condition-id', id );
	row.querySelector( '.condition-select' ).name = `${ base }[condition]`;
	row.querySelector( '.operator-select' ).name = `${ base }[operator]`;

	return row;
}
