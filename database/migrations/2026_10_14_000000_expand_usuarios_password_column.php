<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('usuarios') && Schema::hasColumn('usuarios', 'contrasena')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->string('contrasena', 255)->change();
            });
        }
    }

    public function down(): void
    {
        // Stored password hashes must not be truncated to the legacy 20-character width.
    }
};
