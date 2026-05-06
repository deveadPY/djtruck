@extends('layouts.app')

@section('title', 'Nuevo Usuario')
@section('page-title', '+ Nuevo Usuario')

@include('partials.form-styles')

@section('content')
<div style="max-width:600px;margin:0 auto;">

    @if($errors->any())
    <div class="flash-error" style="margin-bottom:1rem;">
        <ul style="margin:0;padding-left:1.2rem;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('usuarios.store') }}">
        @csrf

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header"><h3 style="margin:0;font-size:1rem;font-weight:600;">Datos del Usuario</h3></div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Nombre completo *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required autofocus>
                    </div>

                    <div class="form-group full">
                        <label>Email *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required>
                    </div>

                    <div class="form-group">
                        <label>Contraseña *</label>
                        <input type="password" name="password" required minlength="8" autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label>Confirmar contraseña *</label>
                        <input type="password" name="password_confirmation" required>
                    </div>

                    <div class="form-group">
                        <label>Rol *</label>
                        <select name="role" required>
                            <option value="">— Seleccionar rol —</option>
                            @foreach($roles as $rol)
                            <option value="{{ $rol->name }}" {{ old('role') == $rol->name ? 'selected' : '' }}>
                                {{ ucfirst($rol->name) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select name="activo">
                            <option value="1" {{ old('activo', '1') == '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ old('activo') == '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:1rem;">
            <a href="{{ route('usuarios.index') }}" class="btn" style="background:#2a2d3e;color:#e2e8f0;padding:.5rem 1.5rem;text-decoration:none;border-radius:6px;">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="padding:.5rem 1.5rem;">Crear Usuario</button>
        </div>
    </form>
</div>
@endsection
