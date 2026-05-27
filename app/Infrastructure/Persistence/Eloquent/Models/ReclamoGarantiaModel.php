<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReclamoGarantiaModel extends Model
{
    use SoftDeletes;

    protected $table    = 'reclamos_garantia';
    protected $fillable = [
        'garantia_id', 'numero_reclamo', 'fecha_reclamo',
        'descripcion_problema', 'diagnostico', 'solucion_aplicada',
        'estado', 'costo_reparacion_usd', 'cubierto_por_garantia',
        'fecha_resolucion', 'tecnico_asignado_id', 'created_by',
    ];

    protected $casts = [
        'fecha_reclamo'          => 'date',
        'fecha_resolucion'       => 'date',
        'cubierto_por_garantia'  => 'boolean',
        'costo_reparacion_usd'   => 'decimal:4',
        'deleted_at'             => 'datetime',
    ];

    public function garantia(): BelongsTo
    {
        return $this->belongsTo(GarantiaModel::class, 'garantia_id');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'tecnico_asignado_id');
    }
}
