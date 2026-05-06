<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentModel extends Model
{
    use SoftDeletes;

    protected $table    = 'cuotas';
    protected $fillable = [
        'plan_cuotas_id', 'venta_id', 'numero_cuota', 'total_cuotas',
        'tipo_plan', 'moneda', 'capital', 'interes', 'fecha_vencimiento',
        'estado', 'fecha_pago_efectivo', 'monto_pagado', 'interes_mora',
        'caja_cobro_id', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'capital'             => 'decimal:4',
        'interes'             => 'decimal:4',
        'monto_total'         => 'decimal:4',
        'monto_pagado'        => 'decimal:4',
        'interes_mora'        => 'decimal:4',
        'fecha_vencimiento'   => 'date',
        'fecha_pago_efectivo' => 'date',
        'deleted_at'          => 'datetime',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(SaleModel::class, 'venta_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanCuotasModel::class, 'plan_cuotas_id');
    }

    public function isVencida(): bool
    {
        return $this->estado === 'PENDIENTE' && $this->fecha_vencimiento < now()->toDateString();
    }

    public function diasMora(): int
    {
        if (!$this->isVencida()) return 0;
        return now()->diffInDays($this->fecha_vencimiento);
    }

    public function getMontoTotalAttribute(): float
    {
        return (float)$this->capital + (float)$this->interes + (float)$this->interes_mora;
    }
}
