<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComisionCalculadaModel extends Model
{
    use SoftDeletes;

    protected $table    = 'comisiones_calculadas';
    protected $fillable = [
        'venta_id', 'vendedor_id', 'esquema_id', 'fecha_venta',
        'base_calculo_usd', 'porcentaje_aplicado', 'monto_comision_usd',
        'estado', 'fecha_aprobacion', 'fecha_pago',
        'aprobada_por', 'movimiento_caja_id', 'observaciones', 'created_by',
    ];

    protected $casts = [
        'fecha_venta'         => 'date',
        'fecha_aprobacion'    => 'date',
        'fecha_pago'          => 'date',
        'base_calculo_usd'    => 'decimal:4',
        'porcentaje_aplicado' => 'decimal:2',
        'monto_comision_usd'  => 'decimal:4',
        'deleted_at'          => 'datetime',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(SaleModel::class, 'venta_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'vendedor_id');
    }

    public function esquema(): BelongsTo
    {
        return $this->belongsTo(EsquemaComisionModel::class, 'esquema_id');
    }
}
