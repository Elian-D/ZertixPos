<?php

// app/Exports/Catalogs/StatesCatalogExport.php

namespace App\Exports\Catalogs;

use App\Models\Geo\Province;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StatesCatalogExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Province::ordered()->select('id', 'name')->get();
    }

    public function headings(): array
    {
        return ['ID (Referencia)', 'Nombre de Provincia'];
    }
}
