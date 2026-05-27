<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquivalenciaRepuestoModel extends Model
{
    use SoftDeletes;

    protected $table    = 'equivalencias_repuestos';
    protected $fillable = ['repuesto_id', 'codigo_externo', 'fabricante', 'descripcion', 'tipo'];
    protected $casts    = [
        'deleted_at' => 'datetime',
    ];

    public function repuesto(): BelongsTo
    {
        return $this->belongsTo(RepuestoModel::class, 'repuesto_id');
    }
}
