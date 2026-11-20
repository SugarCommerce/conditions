/**
 * The three free-entry fields: what separates entries, and what is accepted
 * as one.
 */

import { describe, expect, test } from 'vitest';

import {
	createEntry,
	FREE_ENTRY,
	isEmailPattern,
	isIpPattern,
	modeOf,
} from '../../src/js/builder/tags';

describe( 'IP addresses and ranges', () => {
	test.each( [
		'192.168.1.1',
		'192.168.1.0/24',
		'10.*',
		'*',
		'2001:db8::1',
		'2001:DB8::/32',
		'::1',
		'fe80::abcd',
		'127.0.0.0/8',
	] )( '%s is accepted', ( term ) => {
		expect( isIpPattern( term ) ).toBe( true );
	} );

	test.each( [
		'',
		'bad_ip',
		'192.168.1.1 ',
		'10.0.0.1,10.0.0.2',
		'localhost',
		'192.168.1.1/24x',
		'gg::1',
		'1.2.3.4-1.2.3.9',
		'<script>',
	] )( '%j is refused', ( term ) => {
		expect( isIpPattern( term ) ).toBe( false );
	} );
} );

describe( 'email patterns', () => {
	test.each( [
		'user@domain.com',
		'@domain.com',
		'.edu',
		'domain.com',
		'first.last-name@sub.domain.co.uk',
		'under_score@x.io',
		'@',
		'a',
		'..edu',
		'@.edu',
	] )( '%s is accepted', ( term ) => {
		expect( isEmailPattern( term ) ).toBe( true );
	} );

	test.each( [
		'',
		'not valid',
		'a,b',
		'user+tag@domain.com',
		'*@domain.com',
		'"quoted"@x.com',
		'<b>@x.com',
		'@ domain.com',
	] )( '%j is refused', ( term ) => {
		expect( isEmailPattern( term ) ).toBe( false );
	} );
} );

describe( 'createEntry', () => {
	test( 'a typed entry is trimmed, and is its own value and label', () => {
		expect( createEntry( '  10.0.0.1 ', isIpPattern ) ).toEqual( {
			id: '10.0.0.1',
			text: '10.0.0.1',
			newTag: true,
		} );
	} );

	test( 'nothing, only spaces, or a refused term is no entry', () => {
		const any = () => true;

		expect( createEntry( '', any ) ).toBeNull();
		expect( createEntry( '   ', any ) ).toBeNull();
		expect( createEntry( undefined, any ) ).toBeNull();
		expect( createEntry( null, any ) ).toBeNull();
		expect( createEntry( 'bad_ip', isIpPattern ) ).toBeNull();
	} );

	test( 'the test sees the trimmed term', () => {
		const seen = [];

		createEntry( '\t@x.com\n', ( term ) => seen.push( term ) );

		expect( seen ).toEqual( [ '@x.com' ] );
	} );
} );

describe( 'the modes', () => {
	const select = ( className ) => {
		const element = document.createElement( 'select' );

		element.className = `value-select ${ className } multiple`;

		return element;
	};

	test( 'tags take anything, split on a comma or a space', () => {
		const mode = modeOf( select( 'conditions-tags-select' ) );

		expect( mode ).toBe( FREE_ENTRY[ 'conditions-tags-select' ] );
		expect( mode.separators ).toEqual( [ ',', ' ' ] );
		expect( createEntry( 'vip & co.', mode.accepts ) ).not.toBeNull();
	} );

	test( 'IPs take addresses and ranges, split on a comma or a space', () => {
		const mode = modeOf( select( 'conditions-ip-select' ) );

		expect( mode.separators ).toEqual( [ ',', ' ' ] );
		expect( mode.accepts ).toBe( isIpPattern );
	} );

	test( 'email patterns split only on a comma', () => {
		const mode = modeOf( select( 'conditions-email-select' ) );

		expect( mode.separators ).toEqual( [ ',' ] );
		expect( mode.accepts ).toBe( isEmailPattern );
	} );

	test( 'any other select takes no typed entries', () => {
		expect( modeOf( select( 'conditions-select2' ) ) ).toBeNull();
		expect( modeOf( select( 'conditions-ajax-select' ) ) ).toBeNull();
	} );
} );

test( 'they accept exactly what the jQuery builder accepted', () => {
	// The expressions as assets/js/conditions.js had them.
	const oldIp = /^[\d.\:\/*a-fA-F]+$/;
	const oldEmail = /^[@\.]?[\w\.\-@]+$/;
	const alphabet = '0123456789abcdefgxyzABCFG.:/*@-_+ ,!"<>\t';
	let seed = 7;
	const random = () => {
		seed = ( seed * 16807 ) % 2147483647;

		return seed / 2147483647;
	};

	for ( let i = 0; i < 5000; i++ ) {
		const length = 1 + Math.floor( random() * 12 );
		let term = '';

		for ( let c = 0; c < length; c++ ) {
			term += alphabet[ Math.floor( random() * alphabet.length ) ];
		}

		expect( [ term, isIpPattern( term ), isEmailPattern( term ) ] ).toEqual(
			[ term, oldIp.test( term ), oldEmail.test( term ) ]
		);
	}
} );
