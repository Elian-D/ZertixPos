<?php

namespace App\Imports;

use App\Enums\TaxIdentifierType;
use App\Models\Clients\Client;
use App\Models\Configuration\EstadosCliente;
use App\Models\Geo\Municipality;
use App\Models\Geo\Province;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ClientsImport implements SkipsEmptyRows, ToCollection, WithChunkReading, WithHeadingRow
{
    private static $provinces;

    private static $municipalitiesByProvince;

    private static $estadosClientes;

    private static $taxTypes;

    private static $initialized = false;

    private static $firstChunk = true;

    // Columnas que el sistema NECESITA para procesar
    const REQUIRED_HEADERS = [
        'tipo', 'nombre_o_razon_social', 'nombre_comercial', 'email', 'telefono',
        'provincia_estado', 'ciudad', 'direccion', 'tipo_identificacion', 'rnc_cedula',
        'estado_cliente',
    ];

    // Columnas que el sistema IGNORARÁ si vienen en el archivo (del export)
    const IGNORED_HEADERS = [
        'fecha_registro', 'ultima_actualizacion',
    ];

    public function __construct()
    {
        if (! self::$initialized) {
            self::$provinces = Province::pluck('id', 'name')->toArray();

            // Municipios agrupados por nombre de provincia, para resolver 'ciudad' sin
            // ambigüedad entre municipios homónimos de provincias distintas.
            self::$municipalitiesByProvince = Municipality::with('province:id,name')
                ->get()
                ->groupBy(fn (Municipality $m) => $m->province->name)
                ->map(fn (Collection $group) => $group->pluck('id', 'name')->toArray())
                ->toArray();

            self::$estadosClientes = EstadosCliente::pluck('id', 'nombre')->toArray();

            self::$taxTypes = collect(TaxIdentifierType::cases())
                ->flatMap(fn (TaxIdentifierType $type) => [
                    strtoupper($type->value) => $type->value,
                    strtoupper($type->label()) => $type->value,
                ])
                ->toArray();

            self::$initialized = true;
        }
    }

    public function collection(Collection $rows)
    {
        if (self::$firstChunk && $rows->isNotEmpty()) {
            $this->validateHeaders($rows->first());
            self::$firstChunk = false;
        }

        $dataToUpsert = [];
        $taxIds = [];

        foreach ($rows as $row) {
            if (! $this->validateRow($row)) {
                continue;
            }

            $taxId = $row['rnc_cedula'];

            if (in_array($taxId, $taxIds)) {
                continue;
            }
            $taxIds[] = $taxId;

            $dataToUpsert[] = [
                'type' => strtolower($row['tipo']) == 'empresa' ? 'company' : 'individual',
                'estado_cliente_id' => self::$estadosClientes[$row['estado_cliente']] ?? null,
                'name' => $row['nombre_o_razon_social'],
                'commercial_name' => $row['nombre_comercial'] ?? null,
                'email' => $row['email'] ?? null,
                'phone' => $row['telefono'] ?? null,
                'provincia_id' => self::$provinces[$row['provincia_estado']] ?? null,
                'municipio_id' => self::$municipalitiesByProvince[$row['provincia_estado']][$row['ciudad'] ?? ''] ?? null,
                'address' => $row['direccion'] ?? null,
                'tax_identifier_type' => self::$taxTypes[strtoupper($row['tipo_identificacion'])] ?? null,
                'tax_id' => $taxId,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        if (! empty($dataToUpsert)) {
            DB::transaction(function () use ($dataToUpsert) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');

                Client::upsert(
                    $dataToUpsert,
                    ['tax_id'], // Clave única para decidir si inserta o actualiza
                    ['type', 'estado_cliente_id', 'name', 'commercial_name', 'email',
                        'phone', 'provincia_id', 'municipio_id', 'address', 'tax_identifier_type', 'updated_at']
                );

                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            });
        }
    }

    private function validateHeaders($firstRow)
    {
        $fileHeaders = array_keys($firstRow->toArray());

        // 1. Validar que no falte ninguna columna obligatoria
        $missing = array_diff(self::REQUIRED_HEADERS, $fileHeaders);
        if (! empty($missing)) {
            throw ValidationException::withMessages([
                'file' => 'Faltan columnas obligatorias: '.implode(', ', $missing),
            ]);
        }

        // 2. Validar si hay columnas extra que NO están ni en obligatorias ni en ignoradas
        $allAllowed = array_merge(self::REQUIRED_HEADERS, self::IGNORED_HEADERS);
        $unknown = array_diff($fileHeaders, $allAllowed);

        if (! empty($unknown)) {
            throw ValidationException::withMessages([
                'file' => 'El archivo contiene columnas no reconocidas: '.implode(', ', $unknown),
            ]);
        }
    }

    private function validateRow($row): bool
    {
        if (empty($row['rnc_cedula'])) {
            return false;
        }
        if (empty($row['tipo']) || ! in_array(strtolower($row['tipo']), ['individual', 'empresa'])) {
            return false;
        }
        if (empty($row['nombre_o_razon_social'])) {
            return false;
        }
        if (! isset(self::$provinces[$row['provincia_estado']])) {
            return false;
        }
        if (! isset(self::$estadosClientes[$row['estado_cliente']])) {
            return false;
        }
        if (! isset(self::$taxTypes[strtoupper($row['tipo_identificacion'])])) {
            return false;
        }

        return true;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
