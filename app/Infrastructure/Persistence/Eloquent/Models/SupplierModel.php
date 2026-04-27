<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierModel extends Model
{
    use SoftDeletes;

    protected $table    = 'proveedores';
    protected $fillable = [
        'ruc_rut_nit', 'razon_social', 'nombre_fantasia', 'pais', 'tipo',
        'moneda_principal', 'email', 'telefono', 'activo', 'created_by',
    ];

    protected $casts = [
        'activo'     => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function facturas(): HasMany
    {
        return $this->hasMany(SupplierInvoiceModel::class, 'proveedor_id');
    }

    public function vehiculos(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'proveedor_id');
    }
}
