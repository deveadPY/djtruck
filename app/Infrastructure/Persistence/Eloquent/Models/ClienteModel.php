<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ClienteModel extends Model
{
    use SoftDeletes;

    protected $table    = 'clientes';
    protected $fillable = [
        'ruc', 'razon_social', 'nombre_fantasia', 'pais',
        'email', 'telefono', 'direccion',
        'linea_credito_usd', 'activo',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'linea_credito_usd' => 'decimal:4',
        'activo'            => 'boolean',
        'deleted_at'        => 'datetime',
    ];

    public function ventas(): HasMany
    {
        return $this->hasMany(SaleModel::class, 'cliente_id');
    }

    public function planes(): HasMany
    {
        return $this->hasMany(PlanCuotasModel::class, 'cliente_id');
    }

    public function getSaldoDeudorUsdAttribute(): float
    {
        return (float) DB::table('cuotas')
            ->join('planes_cuotas', 'cuotas.plan_cuotas_id', '=', 'planes_cuotas.id')
            ->where('planes_cuotas.cliente_id', $this->id)
            ->whereIn('cuotas.estado', ['PENDIENTE', 'VENCIDA', 'EN_MORA', 'PAGADA_PARCIAL'])
            ->whereNull('cuotas.deleted_at')
            ->sum(DB::raw('cuotas.capital + cuotas.interes + cuotas.interes_mora - cuotas.monto_pagado'));
    }

    public function getCreditoDisponibleUsdAttribute(): float
    {
        return max(0, (float)$this->linea_credito_usd - $this->saldo_deudor_usd);
    }
}
