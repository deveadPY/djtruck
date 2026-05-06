<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepuestoModel extends Model
{
    use SoftDeletes;

    protected $table    = 'stock_repuestos';
    protected $fillable = [
        'codigo', 'descripcion', 'marca_compatible', 'unidad_medida',
        'stock_actual', 'stock_minimo', 'costo_promedio_usd',
        'precio_venta_usd', 'activo',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'costo_promedio_usd' => 'decimal:4',
        'precio_venta_usd'   => 'decimal:4',
        'activo'             => 'boolean',
        'deleted_at'         => 'datetime',
    ];

    public function getBajoStockAttribute(): bool
    {
        return $this->stock_actual <= $this->stock_minimo;
    }
}
