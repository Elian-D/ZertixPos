// resources/js/pages/nfc-types.js
import AjaxDataTable from '../components/ajax-datatable/index';

document.addEventListener('DOMContentLoaded', () => {
    AjaxDataTable({
        tableId: 'ncf-types-table',
        formId: 'ncf-types-filters',
        debounce: 500,
        // Eliminamos la configuración de 'chips' ya que es una tabla maestra sin filtros de pipeline
    });
});