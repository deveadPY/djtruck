<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EsquemaComisionModel extends Model
{
    use SoftDeletes;

    protected $table    = 'esquemas_comision';
    protected $fillable = [
        'nombre', 'vendedor_id', 'tipo_calculo',
        'porcentaje', 'monto_fijo_usd', 'escala',
        'vigencia_desde', 'vigencia_hasta', 'activo', 'created_by',
    ];

    protected $casts = [
        'porcentaje'      => 'decimal:2',
        'monto_fijo_usd'  => 'decimal:4',
        'escala'          => 'array',
        'vigencia_desde'  => 'date',
        'vigencia_hasta'  => 'date',
        'activo'          => 'boolean',
        'deleted_at'      => 'datetime',
    ];

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'vendedor_id');
    }
}
