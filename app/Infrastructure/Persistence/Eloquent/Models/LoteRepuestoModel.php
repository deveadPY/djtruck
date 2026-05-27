<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoteRepuestoModel extends Model
{
    use SoftDeletes;

    protected $table    = 'lotes_repuestos';
    protected $fillable = [
        'repuesto_id', 'numero_lote', 'fecha_ingreso', 'fecha_vencimiento',
        'cantidad_inicial', 'cantidad_actual', 'costo_unitario_usd',
        'proveedor_id', 'compra_id', 'estado', 'observaciones',
    ];

    protected $casts = [
        'fecha_ingreso'      => 'date',
        'fecha_vencimiento'  => 'date',
        'cantidad_inicial'   => 'decimal:3',
        'cantidad_actual'    => 'decimal:3',
        'costo_unitario_usd' => 'decimal:4',
        'deleted_at'         => 'datetime',
    ];

    public function repuesto(): BelongsTo
    {
        return $this->belongsTo(RepuestoModel::class, 'repuesto_id');
    }

    public function isVencido(): bool
    {
        return $this->fecha_vencimiento && $this->fecha_vencimiento->isPast();
    }

    public function isAgotado(): bool
    {
        return (float) $this->cantidad_actual <= 0;
    }
}
