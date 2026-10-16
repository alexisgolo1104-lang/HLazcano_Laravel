<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('logs_respaldo') && Schema::hasTable('usuarios')) {
            Schema::create('logs_respaldo', function (Blueprint $table) {
                $table->increments('id_log');
                $table->unsignedInteger('id_usuario');
                $table->dateTime('fecha_generacion')->useCurrent();
                $table->string('nombre_archivo', 255);
                $table->unsignedBigInteger('tamano_bytes')->default(0);
                $table->string('tipo_operacion', 50)->default('exportacion');
                $table->boolean('exito')->default(true);
                $table->text('notas')->nullable();
                $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');
            });
        }
    }

    public function down(): void
    {
        // Keep backup history when rolling back application migrations.
    }
};
