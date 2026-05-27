<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);

    DB::table('clientes')->insert([
        'id' => 1,
        'razon_social' => 'Cliente Test',
        'ruc' => '1234567-8',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

test('GET /api/v1/sales retorna lista paginada', function () {
    SaleModel::create([
        'cliente_id' => 1,
        'numero_venta' => 'V-001',
        'fecha_venta' => '2026-05-01',
        'moneda_venta' => 'USD',
        'precio_venta_usd' => 15000,
        'precio_venta_moneda' => 15000,
        'modalidad_pago' => 'CONTADO',
        'estado' => 'COMPLETADO',
        'created_by' => $this->user->id,
    ]);

    $response = $this->getJson('/api/v1/sales');

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'message', 'data', 'pagination']);
});

test('GET /api/v1/sales/{id} retorna venta específica', function () {
    $venta = SaleModel::create([
        'cliente_id' => 1,
        'numero_venta' => 'V-001',
        'fecha_venta' => '2026-05-01',
        'moneda_venta' => 'USD',
        'precio_venta_usd' => 15000,
        'precio_venta_moneda' => 15000,
        'modalidad_pago' => 'CONTADO',
        'estado' => 'COMPLETADO',
        'created_by' => $this->user->id,
    ]);

    $response = $this->getJson("/api/v1/sales/{$venta->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $venta->id);
});

test('GET /api/v1/sales/{id} retorna 404 si no existe', function () {
    $response = $this->getJson('/api/v1/sales/999');

    $response->assertStatus(404);
});

test('DELETE /api/v1/sales/{id} rechaza si está COMPLETADO', function () {
    $venta = SaleModel::create([
        'cliente_id' => 1,
        'numero_venta' => 'V-001',
        'fecha_venta' => '2026-05-01',
        'moneda_venta' => 'USD',
        'precio_venta_usd' => 15000,
        'precio_venta_moneda' => 15000,
        'modalidad_pago' => 'CONTADO',
        'estado' => 'COMPLETADO',
        'created_by' => $this->user->id,
    ]);

    $response = $this->deleteJson("/api/v1/sales/{$venta->id}");

    $response->assertStatus(409);
});
