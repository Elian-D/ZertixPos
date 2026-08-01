import AjaxDataTable from '../components/ajax-datatable/index';

document.addEventListener('DOMContentLoaded', () => {
    AjaxDataTable({
        tableId: 'invoices-table',
        formId: 'invoices-filters',
        debounce: 800,
        chips: {
            // Búsqueda global (Número de factura o Cliente)
            search: { 
                label: 'Búsqueda' 
            },
            // Filtro por Cliente (Relacional)
            client_id: {
                label: 'Cliente',
                source: 'clients' // Se mapea desde window.filterSources.clients
            },
            // Tipo de factura (Contado/Crédito)
            type: {
                label: 'Tipo Venta',
                source: 'payment_types' // Definido en InvoiceCatalogService
            },
            // Estado legal (Vigente/Anulada)
            status: {
                label: 'Estado',
                source: 'statuses' // Definido en InvoiceCatalogService
            },
            // Formato de impresión (Ticket/Carta/Ruta)
            format_type: {
                label: 'Formato',
                source: 'formats' // Definido en InvoiceCatalogService
            },
            // Rango de Fechas de Emisión
            from_date: { 
                label: 'Desde',
                // Si viene con 'T' (datetime-local), lo limpiamos. Si es solo fecha, reverse normal.
                format: (val) => val ? val.replace('T', ' ').split('-').reverse().join('/') : '' 
            },
            to_date: { 
                label: 'Hasta',
                format: (val) => val ? val.replace('T', ' ').split('-').reverse().join('/') : '' 
            }
        }
    });
});