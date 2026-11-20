/**
 * The operators a condition offers, and the operator select that shows them.
 */

/**
 * Which of PHP's operator lists a condition type uses -- the same answer
 * `Operators::for_type()` gives, so the builder offers what the matcher
 * understands.
 *
 * @param {string}  type     The condition's type.
 * @param {boolean} multiple Whether it takes several values.
 * @return {string} A key of the localized `operators`.
 */
export function listFor( type, multiple ) {
	switch ( type ) {
		case 'number':
		case 'number_unit':
			return 'number';

		case 'text_unit':
			return 'text';

		case 'boolean':
		case 'date':
		case 'time':
		case 'ip':
		case 'email':
		case 'tags':
			return type;

		case 'user':
			return multiple ? 'collection_basic' : 'equality';

		case 'select':
		case 'post':
		case 'term':
		case 'ajax':
			return multiple ? 'collection' : 'equality';

		default:
			return 'text';
	}
}

/**
 * A condition's operators: its own when it names them, else its type's.
 *
 * @param {Object|undefined} condition The condition, as PHP sent it.
 * @param {Object}           operators Every list, by name.
 * @return {Object} Operator => label, in the order to offer them.
 */
export function operatorsFor( condition, operators ) {
	if ( ! condition ) {
		return {};
	}

	if ( condition.operators ) {
		return condition.operators;
	}

	return (
		operators[
			listFor( condition.type || 'text', condition.multiple || false )
		] || {}
	);
}

/**
 * Fill a row's operator select for a condition.
 *
 * Disabled until a condition with operators is chosen. The saved operator is
 * kept when the condition still offers it; otherwise the first is chosen.
 *
 * @param {HTMLSelectElement} select        The `.operator-select`.
 * @param {string}            conditionId   The chosen condition.
 * @param {Object}            ops           Operator => label.
 * @param {string}            savedOperator The saved operator, if any.
 */
export function fillOperators( select, conditionId, ops, savedOperator ) {
	const entries = Object.entries( ops );

	select.replaceChildren(
		...entries.map(
			( [ value, label ] ) => new window.Option( label, value )
		)
	);

	select.disabled = ! conditionId || entries.length === 0;

	if ( savedOperator && ops[ savedOperator ] ) {
		select.value = savedOperator;
	} else if ( entries.length > 0 ) {
		select.value = entries[ 0 ][ 0 ];
	}
}
