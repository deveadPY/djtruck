<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * LeadModel — usa la tabla `consultas_web` existente.
 */
class LeadModel extends Model
{
    protected $table    = 'consultas_web';
    protected $fillable = [
        'vehiculo_id', 'nombre', 'telefono', 'email', 'canal', 'estado', 'mensaje',
        'asignado_a', 'asignado_en', 'contactado_en', 'venta_id',
        'motivo_perdido', 'notas_internas', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'asignado_en'   => 'datetime',
        'contactado_en' => 'datetime',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehiculo_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'asignado_a');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(SaleModel::class, 'venta_id');
    }

    public function interacciones(): HasMany
    {
        return $this->hasMany(LeadInteraccionModel::class, 'lead_id')->orderByDesc('fecha_interaccion');
    }
}
