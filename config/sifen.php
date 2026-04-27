<?php

return [
    'ambiente'          => env('SIFEN_AMBIENTE', 'sandbox'),
    'url_sandbox'       => env('SIFEN_URL_SANDBOX', 'https://sifen-test.set.gov.py/de/ws/sync/recibe.wsdl'),
    'url_produccion'    => env('SIFEN_URL_PRODUCCION', 'https://sifen.set.gov.py/de/ws/sync/recibe.wsdl'),
    'qr_base_url'       => env('SIFEN_QR_BASE_URL', 'https://ekuatia.set.gov.py/consultas/qr'),

    // Datos del emisor
    'ruc_emisor'        => env('SIFEN_RUC_EMISOR', ''),
    'razon_social'      => env('SIFEN_RAZON_SOCIAL', ''),
    'nombre_fantasia'   => env('SIFEN_NOMBRE_FANTASIA', ''),
    'actividad_economica' => env('SIFEN_ACTIVIDAD_ECONOMICA', '4511'),
    'numero_timbrado'   => env('SIFEN_NUMERO_TIMBRADO', ''),
    'establecimiento'   => env('SIFEN_ESTABLECIMIENTO', '001'),
    'punto_expedicion'  => env('SIFEN_PUNTO_EXPEDICION', '001'),
    'tipo_contribuyente'=> env('SIFEN_TIPO_CONTRIBUYENTE', 2),
    'direccion'         => env('SIFEN_DIRECCION', ''),
    'telefono'          => env('SIFEN_TELEFONO', ''),
    'email'             => env('SIFEN_EMAIL', ''),

    // Certificado digital
    'cert_path'         => env('SIFEN_CERT_PATH', storage_path('certs/certificado.p12')),
    'cert_passphrase'   => env('SIFEN_CERT_PASSPHRASE', ''),

    // Timeouts
    'timeout_segundos'  => 30,
    'reintentos'        => 3,
];
