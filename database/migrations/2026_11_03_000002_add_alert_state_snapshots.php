<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('alertas_stock')) {
            Schema::table('alertas_stock', function (Blueprint $table) {
                if (! Schema::hasColumn('alertas_stock', 'stock_alerta')) {
                    $table->decimal('stock_alerta', 10, 2)->nullable();
                }
                if (! Schema::hasColumn('alertas_stock', 'fecha_movimiento_alerta')) {
                    $table->date('fecha_movimiento_alerta')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('alertas_stock')) {
            Schema::table('alertas_stock', function (Blueprint $table) {
                $columns = array_filter([
                    Schema::hasColumn('alertas_stock', 'stock_alerta') ? 'stock_alerta' : null,
                    Schema::hasColumn('alertas_stock', 'fecha_movimiento_alerta') ? 'fecha_movimiento_alerta' : null,
                ]);
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};