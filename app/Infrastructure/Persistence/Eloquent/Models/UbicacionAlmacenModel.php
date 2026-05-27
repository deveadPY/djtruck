<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UbicacionAlmacenModel extends Model
{
    use SoftDeletes;

    protected $table    = 'ubicaciones_almacen';
    protected $fillable = ['codigo', 'descripcion', 'zona', 'estante', 'fila', 'bin', 'activo'];
    protected $casts    = [
        'activo'     => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function repuestos(): HasMany
    {
        return $this->hasMany(RepuestoModel::class, 'ubicacion_id');
    }
}
