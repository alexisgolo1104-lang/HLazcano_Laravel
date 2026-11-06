<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('usuarios') && ! Schema::hasColumn('usuarios', 'debe_cambiar_contrasena')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->boolean('debe_cambiar_contrasena')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('usuarios') && Schema::hasColumn('usuarios', 'debe_cambiar_contrasena')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('debe_cambiar_contrasena');
            });
        }
    }
};