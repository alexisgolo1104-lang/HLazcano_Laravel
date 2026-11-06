<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gastos')) {
            Schema::create('gastos', function (Blueprint $table) {
                $table->increments('id_gastos');
                $table->string('concepto', 150);
                $table->string('categoria', 100)->nullable();
                $table->decimal('monto', 12, 2);
                $table->unsignedInteger('id_tienda');
                $table->unsignedInteger('id_usuario');
                $table->unsignedInteger('id_empleado')->nullable();
                $table->string('notas', 150)->nullable();
                $table->date('fecha');
                $table->index('id_empleado');
                $table->index(['id_tienda', 'fecha']);
            });
        } elseif (! Schema::hasColumn('gastos', 'id_empleado')) {
            Schema::table('gastos', function (Blueprint $table) {
                $table->unsignedInteger('id_empleado')->nullable();
                $table->index('id_empleado');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gastos') && Schema::hasColumn('gastos', 'id_empleado')) {
            Schema::table('gastos', function (Blueprint $table) {
                $table->dropIndex(['id_empleado']);
                $table->dropColumn('id_empleado');
            });
        }
    }
};