/* global window, document */
/*if (! window._babelPolyfill) {
    require('@babel/polyfill');
}*/

import React from 'react';
import ReactDOM from 'react-dom';
import Admin from './AdminApp.js';

import simpleStore from './simpleStore'

simpleStore.set('wpObject', window.critical_css_admin);

document.addEventListener('DOMContentLoaded', function() {
    ReactDOM.render(<Admin/>, document.getElementById('critical-css-admin-app'));
});