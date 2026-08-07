{{-- resources/views/clients/partials/filter-sources.blade.php --}}
<script>
    window.filterSources = {
        estadosClientes: JSON.parse('{!! addslashes(json_encode($estadosClientes->pluck("nombre", "id"))) !!}'),
        state: JSON.parse('{!! addslashes(json_encode($states->pluck("name", "id"))) !!}'),
        tax_type: JSON.parse('{!! addslashes(json_encode($taxIdentifierTypes->pluck("label", "value"))) !!}'),
    };
</script>