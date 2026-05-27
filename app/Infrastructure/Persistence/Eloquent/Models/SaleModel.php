<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleModel extends Model
{
    use SoftDeletes;

    protected $table    = 'ventas';
    protected $fillable = [
        'numero_venta', 'cliente_id', 'vehiculo_id', 'vendedor_id', 'estado',
        'moneda_venta', 'precio_venta_moneda', 'precio_venta_usd', 'tasa_cambio_venta',
        'descuento_moneda', 'descuento_usd', 'tasa_interes_mensual',
        'valor_libro_snapshot', 'margen_bruto_usd', 'margen_pct',
        'observaciones', 'fecha_venta', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'precio_venta_moneda'       => 'decimal:4',
        'precio_venta_usd'          => 'decimal:4',
        'descuento_moneda'          => 'decimal:4',
        'descuento_usd'             => 'decimal:4',
        'tasa_interes_mensual'      => 'decimal:4',
        'tasa_cambio_venta'         => 'decimal:8',
        'valor_libro_snapshot'      => 'decimal:4',
        'margen_bruto_usd'          => 'decimal:4',
        'margen_pct'                => 'decimal:4',
        'fecha_venta'  => 'date',
        'deleted_at'   => 'datetime',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehiculo_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteModel::class, 'cliente_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'vendedor_id');
    }

    public function detallesPago(): HasMany
    {
        return $this->hasMany(PaymentDetailModel::class, 'venta_id');
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(InstallmentModel::class, 'venta_id');
    }

    public function plan(): HasMany
    {
        return $this->hasMany(PlanCuotasModel::class, 'venta_id');
    }

    public function getPrecioFinalUsdAttribute(): float
    {
        return max(0, (float)$this->precio_venta_usd - (float)($this->descuento_usd ?? 0));
    }
}
