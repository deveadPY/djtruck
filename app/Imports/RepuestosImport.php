<?php

declare(strict_types=1);

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class RepuestosImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private int $insertados = 0;
    private int $actualizados = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $codigo = trim((string) ($row['codigo'] ?? ''));

            if (empty($codigo)) {
                continue;
            }

            // Buscar proveedor por nombre si existe en el excel
            $proveedorId = null;
            $proveedorNombre = trim((string) ($row['proveedor'] ?? ''));
            if (!empty($proveedorNombre)) {
                $proveedorId = DB::table('proveedores')
                    ->where('razon_social', 'like', "%{$proveedorNombre}%")
                    ->whereNull('deleted_at')
                    ->value('id');
            }

            $data = [
                'descripcion'       => trim((string) ($row['descripcion'] ?? '')),
                'marca_compatible'  => trim((string) ($row['marca_compatible'] ?? '')) ?: null,
                'unidad_medida'     => trim((string) ($row['unidad_medida'] ?? 'UN')) ?: 'UN',
                'stock_minimo'      => max(0, (float) ($row['stock_minimo'] ?? 0)),
                'costo_promedio_usd'=> max(0, (float) ($row['costo_promedio_usd'] ?? 0)),
                'precio_venta_usd'  => ($row['precio_venta_usd'] ?? null) !== null
                    ? max(0, (float) $row['precio_venta_usd'])
                    : null,
                'proveedor_id'      => $proveedorId,
                'activo'            => true,
                'updated_at'        => now(),
            ];

            $model = DB::table('stock_repuestos')
                ->where('codigo', $codigo)
                ->whereNull('deleted_at')
                ->first();

            if ($model) {
                // Para productos existentes NO actualizamos el stock_actual desde el import
                // ya que este se maneja exclusivamente por el módulo de Compras.
                DB::table('stock_repuestos')
                    ->where('id', $model->id)
                    ->update($data);
                $this->actualizados++;
            } else {
                // Para productos nuevos, el stock inicial SIEMPRE es 0.
                // Todo incremento de stock debe venir de una transacción de Compra.
                DB::table('stock_repuestos')->insert(array_merge($data, [
                    'codigo'       => $codigo,
                    'stock_actual' => 0,
                    'created_by'   => Auth::id(),
                    'created_at'   => now(),
                ]));
                $this->insertados++;
            }
        }
    }

    public function getInsertados(): int
    {
        return $this->insertados;
    }

    public function getActualizados(): int
    {
        return $this->actualizados;
    }
}
