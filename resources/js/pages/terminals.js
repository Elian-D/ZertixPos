import AjaxDataTable from '../components/ajax-datatable/index';

document.addEventListener('DOMContentLoaded', () => {
    AjaxDataTable({
        tableId: 'terminals-table',
        formId: 'terminals-filters',
        debounce: 800,
    });
});
