/**
 * A group and a rule row, from the templates PHP drew (Admin\Editor).
 *
 * The markup and its strings stay in PHP, translated there; the ids and names
 * that differ from one copy to the next are put in here. The templates are
 * inside their builder and looked for there, so a builder copies its own
 * set's rows -- never another set's, nor another plugin's copy's.
 */

/**
 * One of a builder's templates.
 *
 * @param {Element} root The `.conditions-builder`.
 * @param {string}  name The template's `data-template`.
 * @return {Element|null} The `<template>`, or null when the builder has none.
 */
function find( root, name ) {
	return root.querySelector( `template[data-template="${ name }"]` );
}

/**
 * A fresh copy of a template's element.
 *
 * @param {Element} root The `.conditions-builder`.
 * @param {string}  name The template's `data-template`.
 * @return {Element|null} The copy, or null when the builder has no such template.
 */
function copy( root, name ) {
	const template = find( root, name );

	if ( ! template || ! template.content ) {
		return null;
	}

	return template.content.firstElementChild.cloneNode( true );
}

/**
 * A group: "Match all of the following rules" when it is the first, "Or match
 * all of the following rules" after.
 *
 * @param {Element} root  The `.conditions-builder`.
 * @param {string}  id    The group's id.
 * @param {number}  index How many groups come before it.
 * @return {Element|null} The group.
 */
export function groupElement( root, id, index ) {
	const group = copy( root, 'condition-group' );

	if ( ! group ) {
		return null;
	}

	const template = find( root, 'condition-group' );

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
 * @param {Element} root The `.conditions-builder`.
 * @return {Element|null} The connector.
 */
export const connectorElement = ( root ) => copy( root, 'group-connector' );

/**
 * A rule row, its condition and operator selects named for the form.
 *
 * @param {Element} root    The `.conditions-builder`.
 * @param {string}  id      The rule's id.
 * @param {string}  groupId Its group's id.
 * @return {Element|null} The row.
 */
export function rowElement( root, id, groupId ) {
	const row = copy( root, 'condition-row' );

	if ( ! row ) {
		return null;
	}

	const base = `_conditions[${ groupId }][rules][${ id }]`;

	row.setAttribute( 'data-condition-id', id );
	row.querySelector( '.condition-select' ).name = `${ base }[condition]`;
	row.querySelector( '.operator-select' ).name = `${ base }[operator]`;

	return row;
}
