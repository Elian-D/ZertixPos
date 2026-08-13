<?php

namespace Database\Seeders\AppInit;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotencia: el dump son INSERT crudos, sin ON DUPLICATE KEY/INSERT
        // IGNORE — correrlo dos veces sin este guard revienta con clave
        // duplicada en `provinces`. Pasa en la práctica: el Wizard de
        // Instalación (Fase 8) llama a `db:seed` en su paso final aunque
        // GeoDataSeeder ya haya corrido antes (al montar el propio Wizard, o
        // en un `migrate --seed` previo).
        if (DB::table('provinces')->exists()) {
            $this->command?->info('Datos geográficos de RD ya existen, se omite.');

            return;
        }

        $path = database_path('seeders/sql/geo_data_rd.sql');

        if (! file_exists($path)) {
            $this->command?->warn("geo_data_rd.sql no encontrado en {$path}, se omite.");

            return;
        }

        // El esquema de provinces/municipalities lo crea una migración dedicada
        // (2025_12_10_000000_create_provinces_and_municipalities_tables), porque
        // configuraciones_generales/clients/point_of_sales tienen FK hacia esas
        // tablas y las migraciones corren antes que cualquier seeder. Por eso aquí
        // solo se ejecutan los INSERT del dump (no el DROP/CREATE TABLE que trae
        // el archivo tal cual lo exporta mysqldump) — un DROP TABLE sobre una tabla
        // con FKs entrantes fallaría de todos modos.
        //
        // DB::unprepared() ejecuta el SQL crudo por la conexión PDO ya configurada
        // en config/database.php — no depende de un binario `mysql` en el PATH del
        // sistema operativo, así que funciona igual en Sail (Linux) y en Laragon
        // (Windows) sin configuración adicional.
        $sql = file_get_contents($path);

        preg_match_all('/^INSERT INTO .*?;$/ms', $sql, $matches);

        foreach ($matches[0] as $insertStatement) {
            DB::unprepared($insertStatement);
        }

        $this->command?->info('Datos geográficos de RD importados correctamente.');
    }
}
