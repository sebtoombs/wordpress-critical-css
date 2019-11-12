/* global window, document */
/*if (! window._babelPolyfill) {
    require('@babel/polyfill');
}*/

import React from 'react';
import ReactDOM from 'react-dom';
import Admin from './AdminApp.js';
import { toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

import simpleStore from './simpleStore'

simpleStore.set('wpObject', window.critical_css_admin);

toast.configure({
    position: toast.POSITION.BOTTOM_CENTER,
    autoClose: 2000,
    hideProgressBar: true,
    pauseOnFocusLoss: false,

})

document.addEventListener('DOMContentLoaded', function() {
    ReactDOM.render(<Admin/>, document.getElementById('critical-css-admin-app'));
});