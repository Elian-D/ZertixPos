import AjaxDataTable from '../components/ajax-datatable/index';

document.addEventListener('DOMContentLoaded', () => {
    AjaxDataTable({
        tableId: 'sales-table',
        formId: 'sales-filters',
        debounce: 800,
        chips: {
            // Filtro de texto libre (Número de factura, notas)
            search: { 
                label: 'Búsqueda' 
            },
            // Filtro por Cliente (Relacional)
            client_id: {
                label: 'Cliente',
                source: 'clients' // Se alimenta de window.filterSources.clients
            },
            // Filtro por Almacén (Relacional)
            warehouse_id: {
                label: 'Almacén',
                source: 'warehouses' // Se alimenta de window.filterSources.warehouses
            },
            // Filtro por Tipo de Pago (Enum: cash, credit)
            payment_type: {
                label: 'Tipo de Pago',
                source: 'payment_types' // Definido en SaleCatalogService
            },

            // NUEVO: Chip para el método específico
            tipo_pago_id: {
                label: 'Método Detallado',
                source: 'tipo_pagos' // Se alimenta de window.filterSources.tipo_pagos
            },

            pos_session_id: {
                label: 'Turno POS',
                source: 'sessions' // Mostrará el ID o código de sesión
            },
            pos_terminal_id: {
                label: 'Terminal',
                source: 'terminals'
            },
            
            // Filtro por Estado (Enum: completed, canceled)
            status: {
                label: 'Estado',
                source: 'statuses' // Definido en SaleCatalogService
            },
            // Filtros de Rango de Fechas
            from_date: { 
                label: 'Desde',
                format: (val) => val ? val.replace('T', ' ') : '' 
            },
            to_date: { 
                label: 'Hasta',
                format: (val) => val ? val.replace('T', ' ') : '' 
            },
            // Filtros de Rango de Montos
            min_amount: {
                label: 'Monto Mín.',
                format: (val) => `$${parseFloat(val).toLocaleString()}`
            },
            max_amount: {
                label: 'Monto Máx.',
                format: (val) => `$${parseFloat(val).toLocaleString()}`
            }
        }
    });
});