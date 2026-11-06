<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('usuarios') && ! Schema::hasColumn('usuarios', 'sueldo')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->decimal('sueldo', 12, 2)->nullable();
            });
        }

        if (! Schema::hasTable('equipo_trabajo')) {
            Schema::create('equipo_trabajo', function (Blueprint $table) {
                $table->increments('id_empleado');
                $table->string('nombre', 120);
                $table->string('puesto', 100);
                $table->string('telefono', 20)->nullable();
                $table->decimal('sueldo', 12, 2)->nullable();
                $table->unsignedInteger('id_tienda');
                $table->boolean('activo')->default(true);
                $table->date('creado_en');
                $table->date('actualizado_en')->nullable();
                $table->index(['id_tienda', 'activo']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_trabajo');

        if (Schema::hasTable('usuarios') && Schema::hasColumn('usuarios', 'sueldo')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('sueldo');
            });
        }
    }
};