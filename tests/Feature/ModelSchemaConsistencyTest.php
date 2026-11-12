<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

// Evita errores 500 por modelos que declaran columnas inexistentes en la base.
test('cada columna fillable de los modelos existe en su tabla', function () {
    $missing = [];

    foreach (glob(app_path('Infrastructure/Persistence/Eloquent/Models/*Model.php')) as $file) {
        $class = 'App\\Infrastructure\\Persistence\\Eloquent\\Models\\' . basename($file, '.php');
        $model = new $class();
        $table = $model->getTable();

        if (!Schema::hasTable($table)) {
            $missing[] = "{$class}: tabla {$table} no existe";
            continue;
        }

        $diff = array_diff($model->getFillable(), Schema::getColumnListing($table));
        if ($diff) {
            $missing[] = "{$table}: " . implode(', ', $diff);
        }
    }

    expect($missing)->toBe([]);
});
