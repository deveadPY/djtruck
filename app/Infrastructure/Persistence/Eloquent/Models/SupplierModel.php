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
        'moneda_principal', 'dias_credito', 'descuento_pago_anticipado_pct',
        'email', 'telefono', 'direccion', 'ciudad', 'sitio_web',
        'contacto_principal', 'banco', 'cuenta_bancaria',
        'score_actual', 'observaciones', 'activo',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'dias_credito'                  => 'integer',
        'descuento_pago_anticipado_pct' => 'decimal:2',
        'score_actual'                  => 'decimal:2',
        'activo'                        => 'boolean',
        'deleted_at'                    => 'datetime',
    ];

    public function facturas(): HasMany
    {
        return $this->hasMany(SupplierInvoiceModel::class, 'proveedor_id');
    }

    public function vehiculos(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'proveedor_id');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(CalificacionProveedorModel::class, 'proveedor_id');
    }

    public function compras(): HasMany
    {
        return $this->hasMany(PurchaseModel::class, 'proveedor_id');
    }

    public function getScoreColorAttribute(): string
    {
        $score = (float) $this->score_actual;
        if ($score >= 80) return 'success';
        if ($score >= 60) return 'info';
        if ($score >= 40) return 'warning';
        return 'danger';
    }
}
