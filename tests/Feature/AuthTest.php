<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// ── Login ────────────────────────────────────────────────────────────────

test('login muestra formulario', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);
});

test('login con credenciales válidas redirige a dashboard', function () {
    $user = User::factory()->create([
        'email'    => 'admin@test.com',
        'password' => Hash::make('password123'),
        'activo'   => true,
    ]);

    $response = $this->post('/login', [
        'email'    => 'admin@test.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

test('login con credenciales incorrectas devuelve error', function () {
    User::factory()->create([
        'email'    => 'admin@test.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->post('/login', [
        'email'    => 'admin@test.com',
        'password' => 'wrong_password',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});

test('login sin datos requeridos falla validación', function () {
    $response = $this->post('/login', []);
    $response->assertSessionHasErrors(['email', 'password']);
});

// ── Autenticación requerida ───────────────────────────────────────────────

test('dashboard requiere autenticación', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('usuario no autenticado es redirigido al login', function () {
    $protectedRoutes = ['/vehicles', '/clientes', '/ventas', '/repuestos'];

    foreach ($protectedRoutes as $route) {
        $this->get($route)->assertRedirect('/login');
    }
});

// ── Logout ───────────────────────────────────────────────────────────────

test('logout desautentica al usuario', function () {
    $user = User::factory()->create(['activo' => true]);
    $this->actingAs($user);

    $this->get('/logout');
    $this->assertGuest();
});

// ── Usuario activo ────────────────────────────────────────────────────────

test('usuario inactivo no puede iniciar sesión', function () {
    User::factory()->create([
        'email'    => 'inactivo@test.com',
        'password' => Hash::make('password123'),
        'activo'   => false,
    ]);

    $response = $this->post('/login', [
        'email'    => 'inactivo@test.com',
        'password' => 'password123',
    ]);

    // Debe ser rechazado (ya sea por sesión o redirección)
    $this->assertGuest();
});
