<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleImageModel extends Model
{
    use SoftDeletes;

    protected $table    = 'vehiculo_imagenes';
    protected $fillable = [
        'vehiculo_id', 'ruta', 'nombre_original', 'orden', 'es_portada',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'es_portada' => 'boolean',
        'orden'      => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehiculo_id');
    }

    public function getUrlAttribute(): string
    {
        return asset($this->ruta);
    }
}
