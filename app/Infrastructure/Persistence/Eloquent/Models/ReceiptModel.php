<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptModel extends Model
{
    protected $table = 'recibos_cuota';

    protected $fillable = [
        'numero_recibo', 'plan_cuotas_id', 'venta_id', 'tipo',
        'monto_capital', 'monto_interes', 'monto_mora',
        'descuento_anticipo', 'descuento_liquidacion', 'total_pagado',
        'moneda', 'cuotas_ids', 'fecha_pago', 'caja_id',
        'observaciones', 'created_by',
    ];

    protected $casts = [
        'monto_capital'         => 'decimal:4',
        'monto_interes'         => 'decimal:4',
        'monto_mora'            => 'decimal:4',
        'descuento_anticipo'    => 'decimal:4',
        'descuento_liquidacion' => 'decimal:4',
        'total_pagado'          => 'decimal:4',
        'cuotas_ids'            => 'json',
        'fecha_pago'            => 'date',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(SaleModel::class, 'venta_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanCuotasModel::class, 'plan_cuotas_id');
    }
}
