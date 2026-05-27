<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepuestoModel extends Model
{
    use SoftDeletes;

    protected $table    = 'stock_repuestos';
    protected $fillable = [
        'codigo', 'codigo_barras', 'descripcion', 'marca_compatible', 'unidad_medida',
        'categoria_id', 'ubicacion_id', 'proveedor_id',
        'stock_actual', 'stock_comprometido', 'stock_minimo',
        'costo_promedio_usd', 'precio_venta_usd', 'activo',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'stock_actual'       => 'decimal:3',
        'stock_comprometido' => 'decimal:3',
        'stock_minimo'       => 'decimal:3',
        'costo_promedio_usd' => 'decimal:4',
        'precio_venta_usd'   => 'decimal:4',
        'activo'             => 'boolean',
        'deleted_at'         => 'datetime',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaRepuestoModel::class, 'categoria_id');
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(UbicacionAlmacenModel::class, 'ubicacion_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(SupplierModel::class, 'proveedor_id');
    }

    public function equivalencias(): HasMany
    {
        return $this->hasMany(EquivalenciaRepuestoModel::class, 'repuesto_id');
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(LoteRepuestoModel::class, 'repuesto_id');
    }

    public function kardex(): HasMany
    {
        return $this->hasMany(KardexRepuestoModel::class, 'repuesto_id')->orderByDesc('created_at');
    }

    public function getBajoStockAttribute(): bool
    {
        return (float) $this->stock_actual <= (float) $this->stock_minimo
            && (float) $this->stock_minimo > 0;
    }

    public function getStockDisponibleAttribute(): float
    {
        return max(0, (float) $this->stock_actual - (float) ($this->stock_comprometido ?? 0));
    }
}
