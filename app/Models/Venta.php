<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $primaryKey = 'id_venta';

    public $timestamps = false;

    protected $fillable = [
        'folio',
        'id_usuario',
        'id_cliente',
        'id_tienda',
        'descuento',
        'subtotal',
        'total',
        'notas',
        'fecha',
    ];
}
