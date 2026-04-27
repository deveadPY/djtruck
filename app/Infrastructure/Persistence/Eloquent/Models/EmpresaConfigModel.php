<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaConfigModel extends Model
{
    protected $table = 'configuracion_empresa';

    protected $fillable = [
        'nombre_empresa', 'ruc', 'telefono', 'email', 'direccion',
        'ciudad', 'pais', 'sitio_web', 'moneda_base', 'logo_path',
        'prefijo_venta', 'prefijo_factura', 'timbrado', 'vigencia_timbrado',
    ];

    protected $casts = [
        'vigencia_timbrado' => 'date',
    ];

    public function logoUrl(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        // New path: uploads/logos/...
        if (str_starts_with($this->logo_path, 'uploads/')) {
            return asset($this->logo_path);
        }

        // Legacy: old logos stored via Storage::disk('public')
        return asset('storage/' . $this->logo_path);
    }

    /**
     * Absolute filesystem path for PDF rendering (DomPDF needs real file path).
     */
    public function logoAbsPath(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        // New path: uploads/logos/...
        if (str_starts_with($this->logo_path, 'uploads/')) {
            return public_path($this->logo_path);
        }

        // Legacy: storage symlink
        return public_path('storage/' . $this->logo_path);
    }
}
