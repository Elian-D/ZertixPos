<?php

// app/Exports/Catalogs/TaxTypesCatalogExport.php

namespace App\Exports\Catalogs;

use App\Enums\TaxIdentifierType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TaxTypesCatalogExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        return collect(TaxIdentifierType::cases())
            ->map(fn (TaxIdentifierType $type) => ['code' => $type->value, 'name' => $type->label()]);
    }

    public function headings(): array
    {
        return ['Código (Referencia)', 'Nombre de Tipo de Identificación'];
    }
}
