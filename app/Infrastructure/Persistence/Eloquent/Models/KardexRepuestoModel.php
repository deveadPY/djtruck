<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KardexRepuestoModel extends Model
{
    protected $table    = 'kardex_repuestos';
    public $timestamps  = false;
    protected $fillable = [
        'repuesto_id', 'tipo', 'motivo', 'cantidad',
        'costo_unitario_usd', 'saldo_resultante', 'costo_promedio_resultante',
        'referencia_type', 'referencia_id', 'observaciones', 'created_by',
    ];

    protected $casts = [
        'cantidad'                  => 'decimal:3',
        'costo_unitario_usd'        => 'decimal:4',
        'saldo_resultante'          => 'decimal:3',
        'costo_promedio_resultante' => 'decimal:4',
        'created_at'                => 'datetime',
    ];

    public function repuesto(): BelongsTo
    {
        return $this->belongsTo(RepuestoModel::class, 'repuesto_id');
    }
}
