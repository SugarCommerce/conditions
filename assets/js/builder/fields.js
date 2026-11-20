/**
 * The value field for each condition type, as markup.
 *
 * The names are what the form posts and what the save reads back:
 * `_conditions[group][rules][rule][value]`, with `[]` for several values and
 * `[number]` / `[text]` and `[unit]` for the two-part fields. Every value is
 * escaped by `html`.
 */

import { __ } from '@wordpress/i18n';

import { html, raw } from '../lib/dom';

/**
 * A saved value as a list: a list as it is, nothing as none, one as one.
 *
 * @param {*} value The saved value.
 * @return {Array} The values.
 */
export const asList = ( value ) => {
	if ( Array.isArray( value ) ) {
		return value;
	}

	return value ? [ value ] : [];
};

/**
 * The `min`, `max` and `step` a number field was configured with. Any number
 * at all unless a step was given.
 *
 * @param {Object} condition The condition.
 * @return {Object} The attributes.
 */
const bounds = ( condition ) =>
	html`${
		condition.min !== undefined ? html` min="${ condition.min }"` : ''
	}${ condition.max !== undefined ? html` max="${ condition.max }"` : '' }${
		condition.step !== undefined
			? html` step="${ condition.step }"`
			: raw( ' step="any"' )
	}`;

/**
 * The options of a unit select, the saved unit chosen.
 *
 * @param {Object} condition The condition.
 * @param {*}      unit      The saved unit.
 * @return {Object} The options.
 */
const unitOptions = ( condition, unit ) =>
	( condition.units || [] ).map(
		( option ) =>
			html`<option value="${ option.value }"${
				option.value === unit ? raw( ' selected' ) : ''
			}>${ option.label }</option>`
	);

/**
 * A selected option for each saved value: what free-entry and searched
 * fields start with (a searched field relabels them once it has asked).
 *
 * @param {Array} values The saved values.
 * @return {Object[]} The options.
 */
const savedOptions = ( values ) =>
	values.map(
		( value ) =>
			html`<option value="${ value }" selected>${ value }</option>`
	);

/**
 * A plain input: text, date or time.
 *
 * Not `value || ''`: that turns a stored `0` into an empty field, and a
 * "first-time customer" rule (`order_count == 0`) looked as if it had no
 * value. `html` writes null and undefined as nothing.
 *
 * @param {string} type      The input type.
 * @param {string} name      The field name.
 * @param {Object} condition The condition.
 * @param {*}      value     The saved value.
 * @return {Object} The input.
 */
const input = ( type, name, condition, value ) =>
	html`<input type="${ type }" class="value-input" name="${ name }" value="${ value }" placeholder="${
		condition.placeholder || ''
	}">`;

/**
 * A number.
 *
 * @param {string} name      The field name.
 * @param {Object} condition The condition.
 * @param {*}      value     The saved value.
 * @return {Object} The input.
 */
const number = ( name, condition, value ) =>
	html`<input type="number" class="value-input" name="${ name }" value="${ value }"${ bounds(
		condition
	) } placeholder="${ condition.placeholder || '' }">`;

/**
 * A number and its unit: `{ number, unit }`.
 *
 * @param {string} name      The field name.
 * @param {Object} condition The condition.
 * @param {*}      value     The saved value.
 * @return {Object} The fields.
 */
function numberWithUnit( name, condition, value ) {
	const isPair = value && typeof value === 'object';

	return html`<div class="number-with-unit"><input type="number" class="number-input" name="${ name }[number]" value="${
		isPair ? value.number : value
	}"${ bounds( condition ) } placeholder="${
		condition.placeholder || ''
	}"><select class="unit-select" name="${ name }[unit]">${ unitOptions(
		condition,
		isPair ? value.unit : ''
	) }</select></div>`;
}

/**
 * Text and its unit: `{ text, unit }`.
 *
 * @param {string} name      The field name.
 * @param {Object} condition The condition.
 * @param {*}      value     The saved value.
 * @return {Object} The fields.
 */
function textWithUnit( name, condition, value ) {
	const isPair = value && typeof value === 'object';
	let text = '';

	if ( isPair ) {
		text = value.text;
	} else if ( typeof value === 'string' ) {
		text = value;
	}

	return html`<div class="text-with-unit"><input type="text" class="text-input" name="${ name }[text]" value="${ text }" placeholder="${
		condition.placeholder || ''
	}"><select class="unit-select" name="${ name }[unit]">${ unitOptions(
		condition,
		isPair ? value.unit : ''
	) }</select></div>`;
}

/**
 * A condition's fixed options as a list of `{ value, label }`.
 *
 * PHP sends a list, or value => label when the options were written that way;
 * anything else is no options.
 *
 * @param {*} options The condition's options.
 * @return {Array} The options.
 */
export function optionList( options ) {
	if (
		options &&
		! Array.isArray( options ) &&
		typeof options === 'object'
	) {
		return Object.entries( options ).map( ( [ value, label ] ) => ( {
			value,
			label,
		} ) );
	}

	return Array.isArray( options ) ? options : [];
}

/**
 * A select of fixed options, one value or several.
 *
 * A saved value that is no longer offered -- a browser name the user-agent
 * library has since retired, say -- stays selected. Dropping it here would
 * delete it from the rule on the next save, and the rule still matches on it.
 *
 * @param {string} name      The field name.
 * @param {Object} condition The condition.
 * @param {*}      value     The saved value.
 * @param {Object} i18n      The builder's strings.
 * @return {Object} The select.
 */
function select( name, condition, value, i18n ) {
	const values = asList( value );
	const offered = [];

	const options = optionList( condition.options ).map( ( option ) => {
		const optionValue = option.value !== undefined ? option.value : option;
		const optionLabel = option.label !== undefined ? option.label : option;

		offered.push( String( optionValue ) );

		return html`<option value="${ optionValue }"${
			values.includes( String( optionValue ) ) ? raw( ' selected' ) : ''
		}>${ optionLabel }</option>`;
	} );

	const retired = values.filter(
		( saved ) => saved !== '' && ! offered.includes( String( saved ) )
	);

	return html`<select class="value-select conditions-select2${
		condition.multiple ? ' multiple' : ''
	}" name="${ condition.multiple ? name + '[]' : name }"${
		condition.multiple ? raw( ' multiple' ) : ''
	} data-placeholder="${ condition.placeholder || i18n.selectValue }">${
		// An empty option lets a single select show its placeholder and clear.
		condition.multiple ? '' : raw( '<option value=""></option>' )
	}${ options }${ savedOptions( retired ) }</select>`;
}

/**
 * A select the admin types entries into: tags, IPs or email patterns.
 *
 * @param {string} className   Its mode's class (tags.js).
 * @param {string} placeholder The placeholder when the condition has none.
 * @param {string} name        The field name.
 * @param {Object} condition   The condition.
 * @param {*}      value       The saved value.
 * @return {Object} The select.
 */
const freeEntry = ( className, placeholder, name, condition, value ) =>
	html`<select class="value-select ${ className } multiple" name="${ name }[]" multiple data-placeholder="${
		condition.placeholder || placeholder
	}">${ savedOptions( asList( value ) ) }</select>`;

/**
 * A select that searches posts, terms or users over REST.
 *
 * @param {string} name      The field name.
 * @param {Object} condition The condition.
 * @param {*}      value     The saved value.
 * @param {Object} i18n      The builder's strings.
 * @return {Object} The select.
 */
function searched( name, condition, value, i18n ) {
	const type = condition.type;
	let role = '';

	if ( type === 'user' && condition.role ) {
		role = Array.isArray( condition.role )
			? condition.role.join( ',' )
			: condition.role;
	}

	return html`<select class="value-select conditions-ajax-select${
		condition.multiple ? ' multiple' : ''
	}" name="${ condition.multiple ? name + '[]' : name }"${
		condition.multiple ? raw( ' multiple' ) : ''
	} data-type="${ type }" data-placeholder="${
		condition.placeholder || i18n.selectValue
	}"${
		type === 'post' && condition.post_type
			? html` data-post-type="${ condition.post_type }"`
			: ''
	}${
		type === 'term' && condition.taxonomy
			? html` data-taxonomy="${ condition.taxonomy }"`
			: ''
	}${ role ? html` data-role="${ role }"` : '' }>${ savedOptions(
		asList( value )
	) }</select>`;
}

/**
 * A select that searches through the condition's own `ajax` callback.
 *
 * @param {string} name        The field name.
 * @param {Object} condition   The condition.
 * @param {string} conditionId The condition's id.
 * @param {*}      value       The saved value.
 * @param {Object} context     `{ i18n, setId }`.
 * @return {Object} The select.
 */
const custom = ( name, condition, conditionId, value, context ) =>
	html`<select class="value-select conditions-custom-ajax-select${
		condition.multiple ? ' multiple' : ''
	}" name="${ condition.multiple ? name + '[]' : name }"${
		condition.multiple ? raw( ' multiple' ) : ''
	} data-type="ajax" data-set-id="${ context.setId }" data-condition-id="${ conditionId }" data-placeholder="${
		condition.placeholder || context.i18n.selectValue
	}">${ savedOptions( asList( value ) ) }</select>`;

/**
 * The value field for a rule.
 *
 * @param {Object} context            What the builder knows.
 * @param {Object} context.conditions Every condition, by id.
 * @param {Object} context.i18n       The builder's strings.
 * @param {string} context.setId      The set being edited.
 * @param {string} conditionId        The chosen condition.
 * @param {string} groupId            The rule's group.
 * @param {string} ruleId             The rule.
 * @param {*}      value              The saved value.
 * @return {string} The markup; empty for a yes/no condition, which is all operator.
 */
export function valueField( context, conditionId, groupId, ruleId, value ) {
	const condition = context.conditions[ conditionId ];

	if ( ! condition ) {
		return String(
			html`<input type="text" class="value-input" disabled placeholder="${ context.i18n.selectCondition }">`
		);
	}

	const name = `_conditions[${ groupId }][rules][${ ruleId }][value]`;

	switch ( condition.type || 'text' ) {
		case 'number':
			return String( number( name, condition, value ) );

		case 'number_unit':
			return String( numberWithUnit( name, condition, value ) );

		case 'text_unit':
			return String( textWithUnit( name, condition, value ) );

		case 'select':
			return String( select( name, condition, value, context.i18n ) );

		case 'tags':
			return String(
				freeEntry(
					'conditions-tags-select',
					__( 'Type and press Enter…', 'sugarcommerce' ),
					name,
					condition,
					value
				)
			);

		case 'ip':
			return String(
				freeEntry(
					'conditions-ip-select',
					__( 'Enter IP or CIDR, press Enter…', 'sugarcommerce' ),
					name,
					condition,
					value
				)
			);

		case 'email':
			return String(
				freeEntry(
					'conditions-email-select',
					__( 'Enter email pattern, press Enter…', 'sugarcommerce' ),
					name,
					condition,
					value
				)
			);

		case 'post':
		case 'term':
		case 'user':
			return String( searched( name, condition, value, context.i18n ) );

		case 'ajax':
			return String(
				custom( name, condition, conditionId, value, context )
			);

		case 'date':
			return String( input( 'date', name, condition, value ) );

		case 'time':
			return String( input( 'time', name, condition, value ) );

		case 'boolean':
			return '';

		default:
			return String( input( 'text', name, condition, value ) );
	}
}
