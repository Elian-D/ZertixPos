<x-app-layout title="Movimientos de Tesorería">
    <div class="p-4 md:p-6 flex flex-col gap-6">
        <livewire:app.finance.financial-movement-table :accountId="$accountId" />
    </div>
</x-app-layout>
