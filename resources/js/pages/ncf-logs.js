import AjaxDataTable from '../components/ajax-datatable/index';

document.addEventListener('DOMContentLoaded', () => {
    AjaxDataTable({
        tableId: 'ncf-logs-table',
        formId: 'ncf-logs-filters',
        debounce: 500,
        chips: {
            // Búsqueda por NCF exacto o parcial
            search: { 
                label: 'Búsqueda' 
            },
            // Filtro por Tipo de NCF (B01, B02, etc.)
            ncf_type_id: {
                label: 'Tipo de Comprobante',
                source: 'ncf_types' 
            },
            // Filtro por Estado (Utilizado, Anulado)
            status: {
                label: 'Estado',
                source: 'statuses' 
            },
            // Rango de Fechas
            from_date: { 
                label: 'Desde',
                format: (val) => val ? val.replace('T', ' ').split('-').reverse().join('/') : '' 
            },
            to_date: { 
                label: 'Hasta',
                format: (val) => val ? val.replace('T', ' ').split('-').reverse().join('/') : '' 
            }
        }
    });
});