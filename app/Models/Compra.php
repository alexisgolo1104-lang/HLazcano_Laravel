<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $table = 'compras';

    protected $primaryKey = 'id_compra';

    public $timestamps = false;

    protected $fillable = [
        'folio',
        'id_proveedor',
        'id_usuario',
        'id_tienda',
        'total',
        'notas',
        'fecha',
    ];
}
