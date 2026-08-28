<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pos_terminals', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            
            // Relación con Almacenes (De dónde descuenta esta terminal)
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->onDelete('restrict');

        // Cambiado a nullable para que el proceso automatizado funcione
            $table->foreignId('cash_account_id')
                ->nullable() 
                ->constrained('accounting_accounts')
                ->onDelete('restrict');

            // Configuración de Facturación por defecto
            $table->foreignId('default_ncf_type_id')
                ->nullable()
                ->constrained('ncf_types')
                ->onDelete('set null');

            $table->foreignId('default_client_id')
                ->nullable()
                ->constrained('clients')
                ->onDelete('set null');

            // Atributos de Interfaz y Hardware
            $table->boolean('is_mobile')->default(false); // Para lógica Sunmi/Móvil
            // Cambiamos printer_format a nullable
            $table->string('printer_format')->nullable();
            
            // Añadimos campos que podrían ser específicos
            $table->boolean('auto_print_receipt')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_terminals');
    }
};
