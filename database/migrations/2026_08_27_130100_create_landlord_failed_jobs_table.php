<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Solo `failed_jobs` para la conexión central — REQ-1.9/REQ-1.12, v1.3.0 Fase 1.
// Con QUEUE_CONNECTION=redis, `jobs`/`job_batches` (colas y Bus::batch(), no
// usado en el proyecto) ya no hacen falta en ninguna conexión — Redis guarda
// esas colas él mismo. `failed_jobs` sigue siendo necesaria igual: Laravel la
// consulta siempre contra la conexión activa (config/queue.php 'failed'),
// sin importar el driver de la cola.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};
