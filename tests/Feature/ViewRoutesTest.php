<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Un route('x') inexistente en una vista revienta la página con un 500.
test('todas las rutas nombradas que usan las vistas existen', function () {
    $definidas = array_keys(Route::getRoutes()->getRoutesByName());
    $faltantes = [];

    foreach (glob(resource_path('views') . '/{,*/,*/*/}*.blade.php', GLOB_BRACE) as $vista) {
        preg_match_all("/route\('([^']+)'/", file_get_contents($vista), $m);
        foreach (array_unique($m[1]) as $nombre) {
            if (!in_array($nombre, $definidas, true)) {
                $faltantes[] = str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $vista) . " → {$nombre}";
            }
        }
    }

    expect($faltantes)->toBe([]);
});
