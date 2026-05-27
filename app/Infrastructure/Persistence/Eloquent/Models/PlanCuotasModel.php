<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanCuotasModel extends Model
{
    use SoftDeletes;

    protected $table    = 'planes_cuotas';
    protected $fillable = [
        'venta_id', 'cliente_id', 'tipo_plan', 'moneda',
        'capital_total', 'capital_total_usd', 'numero_cuotas',
        'tasa_interes_mensual', 'fecha_primera_cuota', 'estado',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'capital_total'        => 'decimal:4',
        'capital_total_usd'    => 'decimal:4',
        'tasa_interes_mensual' => 'decimal:4',
        'fecha_primera_cuota'  => 'date',
        'deleted_at'           => 'datetime',
    ];

    public function cuotas(): HasMany
    {
        return $this->hasMany(InstallmentModel::class, 'plan_cuotas_id')->orderBy('numero_cuota');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(SaleModel::class, 'venta_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteModel::class, 'cliente_id');
    }

    public function getCuotasPagadasAttribute(): int
    {
        return $this->cuotas()->where('estado', 'PAGADA')->count();
    }

    public function getSaldoPendienteUsdAttribute(): float
    {
        return (float) $this->cuotas()
            ->whereIn('estado', ['PENDIENTE', 'VENCIDA', 'EN_MORA', 'PAGADA_PARCIAL'])
            ->sum(\Illuminate\Support\Facades\DB::raw('capital + interes + interes_mora - monto_pagado'));
    }
}
