<?php

// Solo los valores que cambian respecto a los de Laravel (Laravel 11 combina el resto).
return [
    'locale'          => env('APP_LOCALE', 'es'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale'    => env('APP_FAKER_LOCALE', 'es_ES'),
];
