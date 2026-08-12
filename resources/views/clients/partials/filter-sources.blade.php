{{-- resources/views/clients/partials/filter-sources.blade.php --}}
<script>
    window.filterSources = {
        state: JSON.parse('{!! addslashes(json_encode($states->pluck("name", "id"))) !!}'),
        tax_type: JSON.parse('{!! addslashes(json_encode($taxIdentifierTypes->pluck("label", "value"))) !!}'),
    };
</script>