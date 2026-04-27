<?php

declare(strict_types=1);

namespace App\Infrastructure\Settings;

use App\Infrastructure\Persistence\Eloquent\Models\EmpresaConfigModel;
use Illuminate\Support\Facades\Cache;

class EmpresaSettings
{
    public static function get(): EmpresaConfigModel
    {
        return Cache::remember('empresa_config', 3600, function () {
            $config = EmpresaConfigModel::first();
            if (!$config) {
                $config = EmpresaConfigModel::create([
                    'nombre_empresa' => 'ERP Camiones & Repuestos',
                    'pais'           => 'Paraguay',
                    'moneda_base'    => 'USD',
                    'prefijo_venta'  => 'V',
                    'prefijo_factura' => 'F',
                ]);
            }
            return $config;
        });
    }

    public static function forget(): void
    {
        Cache::forget('empresa_config');
    }
}
