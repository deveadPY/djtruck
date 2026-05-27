<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GarantiaModel extends Model
{
    use SoftDeletes;

    protected $table    = 'garantias';
    protected $fillable = [
        'venta_id', 'vehiculo_id', 'repuesto_id', 'tipo',
        'inicio', 'vencimiento', 'km_inicio', 'km_limite',
        'cobertura', 'exclusiones', 'estado',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'inicio'      => 'date',
        'vencimiento' => 'date',
        'deleted_at'  => 'datetime',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(SaleModel::class, 'venta_id');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehiculo_id');
    }

    public function repuesto(): BelongsTo
    {
        return $this->belongsTo(RepuestoModel::class, 'repuesto_id');
    }

    public function reclamos(): HasMany
    {
        return $this->hasMany(ReclamoGarantiaModel::class, 'garantia_id');
    }

    public function isVigente(): bool
    {
        return $this->estado === 'VIGENTE' && $this->vencimiento >= now()->toDateString();
    }
}
