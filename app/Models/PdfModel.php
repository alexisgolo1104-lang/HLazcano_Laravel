<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class PdfModel
{
    public function getTicketCompleto(int $id, ?int $storeId = null): ?array
    {
        $venta = DB::table('ventas as v')
            ->leftJoin('clientes as c', 'v.id_cliente', '=', 'c.id_cliente')
            ->leftJoin('tiendas as t', 'v.id_tienda', '=', 't.id_tienda')
            ->leftJoin('usuarios as u', 'v.id_usuario', '=', 'u.id_usuario')
            ->where('v.id_venta', $id)
            ->when($storeId, fn ($query) => $query->where('v.id_tienda', $storeId))
            ->first([
                'v.*',
                'c.nombre as cliente_nombre',
                'c.correo as cliente_correo',
                'c.telefono as cliente_telefono',
                't.nombre as sucursal_nombre',
                't.direccion as sucursal_direccion',
                't.telefono as sucursal_telefono',
                'u.nombre as vendedor',
            ]);

        if (! $venta) {
            return null;
        }

        $detalles = DB::table('detalle_venta as d')
            ->join('productos as p', 'd.id_producto', '=', 'p.id_producto')
            ->join('catalogo_productos as c', 'p.id_catalogo', '=', 'c.id_catalogo')
            ->leftJoin('unidades as u', 'p.id_unidad', '=', 'u.id_unidad')
            ->where('d.id_venta', $id)
            ->orderBy('d.id_detalle')
            ->get([
                'd.cantidad',
                'd.precio_unit',
                'd.subtotal',
                'c.nombre as nombre_producto',
                'p.presentacion',
                'u.codigo as unidad',
            ]);

        return ['venta' => $venta, 'detalles' => $detalles];
    }
}
