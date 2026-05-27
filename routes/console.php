<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Schedulers automáticos ──────────────────────────────────────────────
Schedule::command('erp:update-exchange-rates')
    ->dailyAt('08:00')
    ->timezone('America/Asuncion')
    ->withoutOverlapping()
    ->description('Actualiza tasas de cambio desde BCN Paraguay');

Schedule::command('erp:check-overdue-installments')
    ->dailyAt('07:00')
    ->timezone('America/Asuncion')
    ->withoutOverlapping()
    ->description('Marca cuotas vencidas y envía notificaciones');

Schedule::command('erp:notify-low-stock')
    ->dailyAt('08:30')
    ->timezone('America/Asuncion')
    ->withoutOverlapping()
    ->description('Verifica repuestos con stock bajo mínimo y registra alertas');

Schedule::command('erp:backup --keep=14')
    ->dailyAt('03:00')
    ->timezone('America/Asuncion')
    ->withoutOverlapping()
    ->description('Backup diario de base de datos (14 días retención)');

Schedule::command('erp:backup --include-uploads --keep=4')
    ->weeklyOn(0, '03:30')
    ->timezone('America/Asuncion')
    ->withoutOverlapping()
    ->description('Backup semanal completo (BD + uploads, 4 semanas retención)');
