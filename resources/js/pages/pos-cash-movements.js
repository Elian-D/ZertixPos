import AjaxDataTable from '../components/ajax-datatable/index';

document.addEventListener('DOMContentLoaded', () => {
    AjaxDataTable({
        tableId: 'cash-movements-table',
        formId: 'cash-movements-filters',
        debounce: 800,
        chips: {
            session_id: {
                label: 'Sesión',
                source: 'sessions'
            },
            user_id: {
                label: 'Usuario',
                source: 'users'
            },
            type: {
                label: 'Tipo',
                source: 'types'
            },
            from_date: { 
                label: 'Inicia',
                format: (val) => val ? val.replace('T', ' ') : '' 
            },
            to_date: { 
                label: 'Termina',
                format: (val) => val ? val.replace('T', ' ') : ''
            }
        }
    });
});