<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * REQ-2.7 (v1.3.0): confirmado con un error real, no una sospecha —
     * `UserController::destroy()` hacía `$user->delete()` (hard-delete) y
     * revienta con una violación de FK en cuanto el usuario tiene actividad
     * real (`payments.created_by`, etc.), porque `created_by`/`user_id` son
     * relaciones de auditoría que no deben borrarse en cascada (ver
     * docs/features/v1.3.0.md §2.7 punto 3 para el error reproducido).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
