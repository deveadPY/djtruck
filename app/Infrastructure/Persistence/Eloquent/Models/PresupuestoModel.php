<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PresupuestoModel extends Model
{
    use SoftDeletes;

    protected $table    = 'presupuestos';
    protected $fillable = [
        'numero_presupuesto', 'cliente_id', 'lead_id', 'vendedor_id', 'estado',
        'fecha_emision', 'vigencia_hasta', 'moneda', 'tasa_cambio',
        'subtotal_usd', 'descuento_usd', 'total_usd',
        'modalidad_pago_sugerida', 'cuotas_sugeridas',
        'observaciones', 'terminos_condiciones',
        'enviado_at', 'aceptado_at', 'convertido_at', 'venta_id',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'fecha_emision'  => 'date',
        'vigencia_hasta' => 'date',
        'tasa_cambio'    => 'decimal:8',
        'subtotal_usd'   => 'decimal:4',
        'descuento_usd'  => 'decimal:4',
        'total_usd'      => 'decimal:4',
        'enviado_at'     => 'datetime',
        'aceptado_at'    => 'datetime',
        'convertido_at'  => 'datetime',
        'deleted_at'     => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteModel::class, 'cliente_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadModel::class, 'lead_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'vendedor_id');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(SaleModel::class, 'venta_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PresupuestoItemModel::class, 'presupuesto_id');
    }
}
