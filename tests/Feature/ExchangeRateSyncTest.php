<?php

declare(strict_types=1);

use App\Infrastructure\Currency\LaMonedaRateSync;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** HTML mínimo con la misma estructura que la portada de La Moneda (panel visible por sucursal). */
function laMonedaHtml(string $usdGs = '6.150', string $usdBrl = '5,600'): string
{
    $fila = fn ($m1, $m2, $c, $v) => "<tr data-moneda=\"{$m1}\" data-moneda2=\"{$m2}\"><td class=\"lm-currency-cell\">{$m1}</td>"
        . "<td class=\"lm-rate-cell text-end\">{$c}</td><td class=\"lm-rate-cell text-end\">{$v}</td></tr>";

    return '<html><body><main>'
        . '<!-- <div id="pills-casa_matriz">oculto</div> -->'
        . '<div id="trading-panel-casa_matriz"><table><tbody>'
        . $fila('DOLAR', 'GUARANI', '5.950', $usdGs)
        . $fila('REAL', 'GUARANI', '1.060', '1.140')
        . $fila('DOLAR', 'REAL', '5,450', $usdBrl)
        . '</tbody></table></div></main></body></html>';
}

beforeEach(function () {
    Cache::flush();
    config(['erp.currency.lamoneda.url' => 'https://lamoneda.test/', 'erp.currency.lamoneda.sucursal' => 'casa_matriz']);
});

test('lee dólar/guaraní y dólar/real del panel visible', function () {
    $tasas = app(LaMonedaRateSync::class)->parse(laMonedaHtml(), 'casa_matriz');

    expect($tasas)->toBe([
        'PYG' => ['compra' => 5950.0, 'venta' => 6150.0],
        'BRL' => ['compra' => 5.45, 'venta' => 5.6],
    ]);
});

test('rechaza valores fuera de rango o sucursal inexistente', function () {
    $sync = app(LaMonedaRateSync::class);

    expect(fn () => $sync->parse(laMonedaHtml('61'), 'casa_matriz'))->toThrow(RuntimeException::class)
        ->and(fn () => $sync->parse(laMonedaHtml(), 'sucursal_x'))->toThrow(RuntimeException::class);
});

test('guarda las tasas del día una sola vez y respeta la carga manual', function () {
    Http::fake(['lamoneda.test/*' => Http::sequence()
        ->push(laMonedaHtml())->push(laMonedaHtml())->push(laMonedaHtml('6.180'))]);
    $sync = app(LaMonedaRateSync::class);

    $sync->sync();
    $sync->sync();
    expect(DB::table('tasas_cambio')->count())->toBe(2)
        ->and((float) DB::table('tasas_cambio')->where('moneda_destino', 'PYG')->value('venta'))->toBe(6150.0);

    // Un usuario carga a mano la tasa del día: la automática no la pisa.
    DB::table('tasas_cambio')->insert([
        'fecha' => LaMonedaRateSync::hoy(), 'moneda_destino' => 'PYG', 'compra' => 6000, 'venta' => 6200,
        'created_by' => 7, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $sync->sync();

    expect((float) DB::table('tasas_cambio')->where('moneda_destino', 'PYG')->orderByDesc('id')->value('venta'))->toBe(6200.0);
});

test('el endpoint actualiza desde La Moneda e informa la fuente; si falla usa la última tasa', function () {
    $this->actingAs(User::factory()->create(['activo' => true]));
    DB::table('tasas_cambio')->insert([
        'fecha' => '2026-01-10', 'moneda_destino' => 'PYG', 'compra' => 7000, 'venta' => 7100,
        'created_by' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]);

    Http::fake(['lamoneda.test/*' => Http::sequence()->push('error', 500)->push(laMonedaHtml())]);
    $this->getJson(route('api.cotizaciones.today'))
        ->assertOk()->assertJson(['PYG' => 7100, 'detalle' => ['PYG' => ['fuente' => 'Carga manual'], 'BRL' => null]]);

    Cache::flush(); // libera el bloqueo de 30 minutos
    $this->getJson(route('api.cotizaciones.today'))
        ->assertOk()->assertJson(['PYG' => 6150, 'BRL' => 5.6, 'detalle' => ['PYG' => ['fuente' => 'La Moneda'], 'BRL' => ['fuente' => 'La Moneda']]]);

    // Una fecha pasada devuelve la tasa vigente en ese momento, sin consultar la web.
    $this->getJson(route('api.cotizaciones.today', ['fecha' => '2026-02-01']))
        ->assertOk()->assertJson(['PYG' => 7100]);
});
