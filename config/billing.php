<?php

return [
    /*
    |---------------------------------------------------------------------------
    | Driver activo de facturación electrónica
    |---------------------------------------------------------------------------
    | Soportados: null, generic_api, facturasend, ekuatia, bukeala
    | (Cada driver se mapea a un Adapter en AppServiceProvider).
    */
    'default' => env('BILLING_API_DRIVER', 'null'),

    /*
    |---------------------------------------------------------------------------
    | Drivers
    |---------------------------------------------------------------------------
    */
    'drivers' => [

        'null' => [
            'adapter' => \App\Infrastructure\Billing\Adapters\NullBillingAdapter::class,
        ],

        'generic_api' => [
            'adapter'  => \App\Infrastructure\Billing\Adapters\GenericApiBillingAdapter::class,
            'base_url' => env('BILLING_API_URL', ''),
            'api_key'  => env('BILLING_API_KEY', ''),
            'timeout'  => (int) env('BILLING_API_TIMEOUT', 30),
            'provider_name' => env('BILLING_API_PROVIDER_NAME', 'generic-api'),
        ],

        // Plantilla para integrar un proveedor específico:
        // 'facturasend' => [
        //     'adapter'  => \App\Infrastructure\Billing\Adapters\FacturaSendAdapter::class,
        //     'base_url' => env('FACTURASEND_URL'),
        //     'api_key'  => env('FACTURASEND_TOKEN'),
        //     'ruc'      => env('FACTURASEND_RUC'),
        //     'ambient'  => env('FACTURASEND_AMBIENT', 'test'),
        // ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Configuración fiscal de la empresa
    |---------------------------------------------------------------------------
    */
    'company' => [
        'ruc'             => env('BILLING_COMPANY_RUC', ''),
        'razon_social'    => env('BILLING_COMPANY_NAME', ''),
        'nombre_fantasia' => env('BILLING_COMPANY_TRADE_NAME', ''),
        'establecimiento' => env('BILLING_COMPANY_ESTABLISHMENT', '001'),
        'punto_expedicion'=> env('BILLING_COMPANY_POINT', '001'),
        'timbrado'        => env('BILLING_COMPANY_TIMBRADO', ''),
        'timbrado_vigencia_desde' => env('BILLING_COMPANY_TIMBRADO_FROM', ''),
        'timbrado_vigencia_hasta' => env('BILLING_COMPANY_TIMBRADO_TO', ''),
    ],

    /*
    |---------------------------------------------------------------------------
    | Webhook security
    |---------------------------------------------------------------------------
    | Algunos proveedores envían callbacks asíncronos con el estado final.
    */
    'webhook' => [
        'secret' => env('BILLING_WEBHOOK_SECRET', ''),
    ],
];
