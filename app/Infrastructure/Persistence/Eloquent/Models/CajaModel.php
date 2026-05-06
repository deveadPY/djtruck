<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CajaModel extends Model
{
    protected $table    = 'cajas';
    protected $fillable = [
        'nombre', 'tipo', 'moneda_principal', 'activo',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCajaModel::class, 'caja_id');
    }
}
