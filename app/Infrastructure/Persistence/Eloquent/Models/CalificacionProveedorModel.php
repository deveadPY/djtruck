<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalificacionProveedorModel extends Model
{
    use SoftDeletes;

    protected $table    = 'calificaciones_proveedor';
    protected $fillable = ['proveedor_id', 'criterio', 'puntaje', 'comentario', 'compra_id', 'created_by'];
    protected $casts    = [
        'puntaje'    => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(SupplierModel::class, 'proveedor_id');
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(PurchaseModel::class, 'compra_id');
    }
}
