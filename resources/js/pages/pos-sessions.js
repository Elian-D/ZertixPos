import AjaxDataTable from '../components/ajax-datatable/index';

document.addEventListener('DOMContentLoaded', () => {
    AjaxDataTable({
        tableId: 'pos-sessions-table',
        formId: 'pos-sessions-filters',
        debounce: 800,
        chips: {
            // Filtro por Terminal
            terminal_id: {
                label: 'Terminal',
                source: 'terminals' // window.filterSources.terminals
            },
            // Filtro por Cajero (legacy, igual a opened_by_user_id)
            user_id: {
                label: 'Cajero(a)',
                source: 'users' // window.filterSources.users
            },
            // Filtro por quién abrió el turno
            opened_by_user_id: {
                label: 'Abierto Por',
                source: 'users' // window.filterSources.users
            },
            // Filtro por quién cerró el turno
            closed_by_user_id: {
                label: 'Cerrado Por',
                source: 'users' // window.filterSources.users
            },
            // Filtro por Estado (Abierta/Cerrada)
            status: {
                label: 'Estado',
                source: 'statuses' // window.filterSources.statuses
            },
            // Filtro por motivo de descuadre (solo sesiones cerradas con diferencia)
            difference_reason: {
                label: 'Motivo de Descuadre',
                source: 'difference_reasons' // window.filterSources.difference_reasons
            },
            // Filtros de Fecha de Apertura
            from_date: { 
                label: 'Desde',
                format: (val) => val ? val.replace('T', ' ') : '' 
            },
            to_date: { 
                label: 'Hasta',
                format: (val) => val ? val.replace('T', ' ') : '' 
            }
        }
    });
});