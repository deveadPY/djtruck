<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ERP Camiones & Repuestos — Configuración Principal
    |--------------------------------------------------------------------------
    */

    'app_name'    => env('APP_NAME', 'ERP Camiones & Repuestos'),
    'version'     => '1.0.0',

    // ── Multimoneda ────────────────────────────────────────────────────
    'currency' => [
        'base'      => env('ERP_MONEDA_BASE', 'USD'),
        'enabled'   => explode(',', env('ERP_MONEDAS_HABILITADAS', 'USD,PYG,BRL')),
        'fallback_rates' => [
            'USD_PYG' => (float) env('ERP_TASA_USD_PYG', 7800),
            'USD_BRL' => (float) env('ERP_TASA_USD_BRL', 5.05),
        ],
    ],

    // ── Numeración de documentos ───────────────────────────────────────
    'numbering' => [
        'sale_prefix'     => 'VTA',
        'purchase_prefix' => 'CPR',
        'invoice_prefix'  => 'FAC',
    ],

    // ── Costeo ────────────────────────────────────────────────────────
    'costing' => [
        // Si true, los gastos deben aprobarse antes de aplicarse al valor libro
        'require_expense_approval' => false,

        // Margen mínimo de alerta (porcentaje sobre valor libro)
        'min_margin_alert_pct' => 5.0,
    ],

    // ── Cuotas ────────────────────────────────────────────────────────
    'installments' => [
        'max_cuotas'           => 60,
        'max_tasa_mensual_pct' => 10.0,
        'dias_gracia_mora'     => 5,
        'tasa_mora_diaria_pct' => 0.1,

        // Descuento por pronto pago (anticipo de cuotas)
        'early_payment_discount' => [
            'enabled'            => true,
            'default_pct'        => 5.0,     // 5% sobre capital
            'max_pct'            => 15.0,    // tope máximo
            'proporcional'       => false,   // si true, escala con días de anticipación
        ],

        // Descuento por liquidación del plan completo
        'liquidation_discount' => [
            'enabled'            => true,
            'default_pct'        => 20.0,    // 20% sobre interés no devengado
            'max_pct'            => 50.0,    // tope máximo
        ],
    ],

    // ── Módulos habilitados ───────────────────────────────────────────
    'modules' => [
        'vehicles'     => true,
        'sales'        => true,
        'installments' => true,
        'suppliers'    => true,
        'cash'         => true,
        'reports'      => true,
    ],
];
