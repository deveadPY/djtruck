<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentDetailModel extends Model
{
    use SoftDeletes;

    protected $table    = 'detalles_pago';
    protected $fillable = [
        'venta_id', 'tipo_pago', 'moneda', 'monto_moneda', 'monto_usd',
        'tasa_cambio', 'vehiculo_canje_id', 'plan_cuotas_id',
        'referencia_bancaria', 'banco', 'caja_id', 'observaciones',
        'fecha_pago', 'created_by',
    ];

    protected $casts = [
        'monto_moneda' => 'decimal:4',
        'monto_usd'    => 'decimal:4',
        'tasa_cambio'  => 'decimal:8',
        'fecha_pago'   => 'date',
        'deleted_at'   => 'datetime',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(SaleModel::class, 'venta_id');
    }

    public function vehiculoCanjeado(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehiculo_canje_id');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(CajaModel::class, 'caja_id');
    }
}
