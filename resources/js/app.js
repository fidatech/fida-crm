import * as bootstrap from 'bootstrap';
import $ from 'jquery';
import select2 from 'select2';
import Swal from 'sweetalert2';
import './crm.js';

window.bootstrap = bootstrap;
window.jQuery = window.$ = $;
window.FidaAlert = Swal;
// Select2's CommonJS distribution exports a jQuery plugin registration function.
if (typeof select2 === 'function') select2(window, $);
