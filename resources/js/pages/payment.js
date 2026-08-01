import AjaxDataTable from '../components/ajax-datatable/index';

document.addEventListener('DOMContentLoaded', () => {
    AjaxDataTable({
        tableId: 'payments-table',
        formId: 'payments-filters',
        debounce: 800,
        chips: {
            client_id: {
                label: 'Cliente',
                source: 'clients' // Se cargará de window.filterSources.clients
            },
            tipo_pago_id: {
                label: 'Método de Pago',
                source: 'paymentMethods' // Se cargará de window.filterSources.paymentMethods
            },
            status: {
                label: 'Estado',
                source: 'statuses'
            },
            from_date: { 
                label: 'Inicia',
                format: (val) => val ? val.replace('T', ' ').split('-').reverse().join('/') : '' 
            },
            to_date: { 
                label: 'Termina',
                format: (val) => val ? val.replace('T', ' ').split('-').reverse().join('/') : ''
            },
            min_amount: {
                label: 'Monto mín.',
            },
            max_amount: {
                label: 'Monto máx.',
            },
            search: {
                label: 'Búsqueda'
            }
        }
    });
});