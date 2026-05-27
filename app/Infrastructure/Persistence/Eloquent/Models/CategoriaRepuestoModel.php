<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaRepuestoModel extends Model
{
    use SoftDeletes;

    protected $table    = 'categorias_repuestos';
    protected $fillable = ['parent_id', 'nombre', 'slug', 'descripcion', 'orden', 'activo'];
    protected $casts    = [
        'activo'     => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('orden');
    }

    public function repuestos(): HasMany
    {
        return $this->hasMany(RepuestoModel::class, 'categoria_id');
    }

    /**
     * Devuelve el path completo (Padre > Hijo > Nieto).
     */
    public function fullPath(): string
    {
        $path = [$this->nombre];
        $current = $this->parent;
        while ($current) {
            array_unshift($path, $current->nombre);
            $current = $current->parent;
        }
        return implode(' > ', $path);
    }
}
