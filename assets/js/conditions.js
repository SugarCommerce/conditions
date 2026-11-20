/**
 * The rule builder's script: on a set's edit screen (Admin\Assets).
 *
 * The searched value fields ask the library's REST routes through apiFetch.
 * Each build starts only the builders that name it (lib/config).
 */

import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';

import { init } from './builder/builder';
import { build } from './lib/config';

// This build's builders only: its handle is read as the script runs.
domReady( () => init( apiFetch, build ) );
