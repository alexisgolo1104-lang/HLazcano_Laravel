<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tiendas')
            ->where('id_tienda', 1)
            ->update(['nombre' => 'HLazcano']);
    }

    public function down(): void
    {
        DB::table('tiendas')
            ->where('id_tienda', 1)
            ->update(['nombre' => 'Sucursal 1 - Santa María Texmelucan']);
    }
};
