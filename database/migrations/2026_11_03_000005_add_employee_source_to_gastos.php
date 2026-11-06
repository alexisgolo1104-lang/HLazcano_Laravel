<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gastos') && ! Schema::hasColumn('gastos', 'origen_empleado')) {
            Schema::table('gastos', function (Blueprint $table) {
                $table->string('origen_empleado', 20)->nullable();
                $table->index('origen_empleado');
            });
        }

        if (Schema::hasTable('gastos') && Schema::hasColumn('gastos', 'origen_empleado')) {
            DB::table('gastos')
                ->where('categoria', 'sueldo')
                ->whereNotNull('id_empleado')
                ->whereNull('origen_empleado')
                ->update(['origen_empleado' => 'usuario']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gastos') && Schema::hasColumn('gastos', 'origen_empleado')) {
            Schema::table('gastos', function (Blueprint $table) {
                $table->dropIndex(['origen_empleado']);
                $table->dropColumn('origen_empleado');
            });
        }
    }
};