import * as bootstrap from 'bootstrap';
import jQuery from 'jquery';
import DataTable from 'datatables.net-bs5';
import select2 from 'select2';

import { initBoard } from './components/board';
import { initConfirm } from './components/confirm';
import { initDataTables, initFilteredLinks } from './components/datatable';
import { initNavCollapse } from './components/nav';
import { initSelect2 } from './components/select2';
import { initThemeSwitch } from './components/theme';
import { showFlashToasts, toast } from './components/toast';

// Globals for page scripts and jQuery plugins.
window.bootstrap = bootstrap;
window.$ = window.jQuery = jQuery;
window.DataTable = DataTable;
window.toast = toast;
select2(window, jQuery);

document.addEventListener('DOMContentLoaded', () => {
    initConfirm();
    initDataTables();
    initFilteredLinks();
    initSelect2();
    initThemeSwitch();
    initNavCollapse();
    initBoard();
    showFlashToasts();
});
