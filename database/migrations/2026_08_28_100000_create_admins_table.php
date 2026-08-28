<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Staff del landlord (Súper Admin, soporte) — REQ-1.3, v1.3.0 Fase 1. Tabla
// propia en la conexión central, separada de `users` (negocio, solo por
// tenant desde REQ-1.7) — guard `landlord` distinto del guard `web`.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
