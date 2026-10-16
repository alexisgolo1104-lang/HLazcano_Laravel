<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    protected $primaryKey = 'id_producto';

    public $timestamps = false;

    protected $fillable = [
        'id_catalogo',
        'id_tipo',
        'id_color',
        'id_unidad',
        'id_proveedor',
        'id_anaquel',
        'id_tienda',
        'presentacion',
        'posicion_anaquel',
        'stock_actual',
        'stock_minimo',
        'precio_compra',
        'precio_venta',
        'activo',
        'creado_en',
        'actualizado_en',
    ];
}
