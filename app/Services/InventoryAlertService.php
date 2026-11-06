<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryAlertService
{
    public function syncStore(int $storeId): int
    {
        if (! Schema::hasTable('alertas_stock') || ! Schema::hasTable('productos')) {
            return 0;
        }

        $movementDates = $this->latestMovementDates($storeId);
        $products = DB::table('productos')
            ->where('id_tienda', $storeId)
            ->where('activo', 1)
            ->get(['id_producto', 'stock_actual', 'stock_minimo', 'creado_en', 'actualizado_en']);

        $definitions = [];
        $stockTypes = [];
        foreach ($products as $product) {
            $stock = (float) $product->stock_actual;
            $minimum = (float) $product->stock_minimo;

            if ($stock <= $minimum) {
                $type = 'stock_critico';
            } elseif ($minimum > 0 && $stock <= $minimum * 1.2) {
                $type = 'stock_minimo';
            } else {
                $type = null;
            }

            if ($type !== null) {
                $definitions[$product->id_producto.':'.$type] = [
                    'product_id' => (int) $product->id_producto,
                    'type' => $type,
                    'stock' => $stock,
                    'last_movement' => null,
                ];
                $stockTypes[$product->id_producto] = $type;
            }

            $lastMovement = $movementDates->get($product->id_producto)
                ?? $product->actualizado_en
                ?? $product->creado_en;

            if ($lastMovement && Carbon::parse($lastMovement)->lte(now()->subDays(30))) {
                $definitions[$product->id_producto.':sin_movimiento'] = [
                    'product_id' => (int) $product->id_producto,
                    'type' => 'sin_movimiento',
                    'stock' => $stock,
                    'last_movement' => Carbon::parse($lastMovement)->toDateString(),
                ];
            }
        }

        $openAlerts = DB::table('alertas_stock')
            ->where('id_tienda', $storeId)
            ->where('atendida', 0)
            ->get(['id_alerta', 'id_producto', 'tipo_alerta']);

        foreach ($openAlerts as $alert) {
            $type = $alert->tipo_alerta;
            if ($type === 'stock_bajo' && isset($stockTypes[$alert->id_producto])) {
                $type = $stockTypes[$alert->id_producto];
                DB::table('alertas_stock')->where('id_alerta', $alert->id_alerta)->update(['tipo_alerta' => $type]);
            }

            if (! isset($definitions[$alert->id_producto.':'.$type])) {
                $this->resolveAlert((int) $alert->id_alerta);
            }
        }

        foreach ($definitions as $definition) {
            $productId = $definition['product_id'];
            $type = $definition['type'];
            $latest = DB::table('alertas_stock')
                ->where('id_tienda', $storeId)
                ->where('id_producto', $productId)
                ->where('tipo_alerta', $type)
                ->orderByDesc('id_alerta')
                ->first(['id_alerta', 'atendida', 'accion_tomada', 'stock_alerta', 'fecha_movimiento_alerta']);

            if (! $latest || ($latest->atendida && $latest->accion_tomada === 'resuelta')) {
                DB::table('alertas_stock')->insert([
                    'id_producto' => $productId,
                    'id_tienda' => $storeId,
                    'tipo_alerta' => $type,
                    'atendida' => 0,
                    'fecha_alerta' => now()->toDateString(),
                    'stock_alerta' => $definition['stock'],
                    'fecha_movimiento_alerta' => $definition['last_movement'],
                ]);
            } elseif ($latest->atendida) {
                if ($latest->stock_alerta === null) {
                    DB::table('alertas_stock')->where('id_alerta', $latest->id_alerta)->update([
                        'stock_alerta' => $definition['stock'],
                        'fecha_movimiento_alerta' => $definition['last_movement'],
                    ]);
                } elseif ($this->stateChanged($type, $latest, $definition)) {
                    DB::table('alertas_stock')->where('id_alerta', $latest->id_alerta)->update([
                        'atendida' => 0,
                        'accion_tomada' => null,
                        'id_usuario_aten' => null,
                        'notas_atencion' => null,
                        'fecha_alerta' => now()->toDateString(),
                        'fecha_atencion' => null,
                        'stock_alerta' => $definition['stock'],
                        'fecha_movimiento_alerta' => $definition['last_movement'],
                    ]);
                }
            }
        }

        $attendedAlerts = DB::table('alertas_stock')
            ->where('id_tienda', $storeId)
            ->where('atendida', 1)
            ->where('accion_tomada', '<>', 'resuelta')
            ->get(['id_alerta', 'id_producto', 'tipo_alerta']);

        foreach ($attendedAlerts as $alert) {
            if (! isset($definitions[$alert->id_producto.':'.$alert->tipo_alerta])) {
                DB::table('alertas_stock')
                    ->where('id_alerta', $alert->id_alerta)
                    ->update(['accion_tomada' => 'resuelta']);
            }
        }

        return (int) DB::table('alertas_stock')
            ->where('id_tienda', $storeId)
            ->where('atendida', 0)
            ->count();
    }

    public function latestMovementDates(int $storeId)
    {
        $sources = [];

        if (Schema::hasTable('detalle_venta') && Schema::hasTable('ventas')) {
            $sources[] = DB::table('detalle_venta as d')
                ->join('ventas as v', 'd.id_venta', '=', 'v.id_venta')
                ->where('v.id_tienda', $storeId)
                ->where(function ($query) {
                    $query->whereNull('v.notas')
                        ->orWhere(function ($query) {
                            $query->where('v.notas', 'not like', 'CANCELADA:%')
                                ->where('v.notas', 'not like', 'DEVOLUCION:%');
                        });
                })
                ->select('d.id_producto', 'v.fecha');
        }

        if (Schema::hasTable('detalle_compra') && Schema::hasTable('compras')) {
            $sources[] = DB::table('detalle_compra as d')
                ->join('compras as c', 'd.id_compra', '=', 'c.id_compra')
                ->where('c.id_tienda', $storeId)
                ->select('d.id_producto', 'c.fecha');
        }

        if (Schema::hasTable('movimientos_inventario')) {
            $sources[] = DB::table('movimientos_inventario as m')
                ->join('productos as p', 'm.id_producto', '=', 'p.id_producto')
                ->where('p.id_tienda', $storeId)
                ->select('m.id_producto', 'm.fecha');
        }

        if ($sources === []) {
            return collect();
        }

        $activity = array_shift($sources);
        foreach ($sources as $source) {
            $activity->unionAll($source);
        }

        return DB::query()
            ->fromSub($activity, 'activity')
            ->select('id_producto')
            ->selectRaw('MAX(fecha) as ultima_fecha')
            ->groupBy('id_producto')
            ->pluck('ultima_fecha', 'id_producto');
    }

    private function stateChanged(string $type, object $previous, array $current): bool
    {
        if ($type === 'sin_movimiento') {
            return $previous->fecha_movimiento_alerta !== $current['last_movement'];
        }

        return abs((float) $previous->stock_alerta - $current['stock']) >= 0.01;
    }

    private function resolveAlert(int $alertId): void
    {
        DB::table('alertas_stock')->where('id_alerta', $alertId)->update([
            'atendida' => 1,
            'accion_tomada' => 'resuelta',
            'notas_atencion' => 'Resuelta automáticamente al actualizarse el inventario.',
            'fecha_atencion' => now()->toDateString(),
        ]);
    }
}