/**
 * The rule builder's script: on a set's edit screen (Admin\Assets).
 *
 * The searched value fields ask the library's REST routes through apiFetch.
 */

import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';

import { init } from './builder/builder';

domReady( () => init( apiFetch ) );
