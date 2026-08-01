import AjaxDataTable from '../components/ajax-datatable/index';

document.addEventListener('DOMContentLoaded', () => {
    AjaxDataTable({
        tableId: 'quotes-table',
        formId: 'quotes-filters',
        debounce: 800,
        chips: {
            // Búsqueda global (ID, Cliente o Notas)
            search: { 
                label: 'Búsqueda' 
            },
            // Filtro por Cliente (Relacional)
            customer_id: {
                label: 'Cliente',
                source: 'customers' // Mapea desde window.filterSources.customers
            },
            // Filtro por Vendedor
            user_id: {
                label: 'Vendedor',
                source: 'users'
            },
            // Estado (Draft, Approved, Converted, etc.)
            status: {
                label: 'Estado',
                source: 'statuses'
            },
            // Origen (Backoffice / POS)
            origin: {
                label: 'Origen',
                source: 'origins'
            },
            // Rango de Fechas (Uso estricto de fecha simple como en Clientes)
            from_date: { 
                label: 'Desde',
                format: (val) => val ? val.split('-').reverse().join('/') : '' 
            },
            to_date: { 
                label: 'Hasta',
                format: (val) => val ? val.split('-').reverse().join('/') : '' 
            }
        }
    });
});