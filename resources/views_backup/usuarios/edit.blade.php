@extends('layouts.app')

@section('title', 'Editar Usuario: ' . $usuario->name)
@section('page-title', '✏️ Editar Usuario')

@include('partials.form-styles')

@section('content')
<div style="max-width:600px;margin:0 auto;">

    @if(session('success'))
    <div class="flash-success" style="margin-bottom:1rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="flash-error" style="margin-bottom:1rem;">
        <ul style="margin:0;padding-left:1.2rem;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
        @csrf @method('PUT')

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header"><h3 style="margin:0;font-size:1rem;font-weight:600;">Datos del Usuario</h3></div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Nombre completo *</label>
                        <input type="text" name="name" value="{{ old('name', $usuario->name) }}" required>
                    </div>

                    <div class="form-group full">
                        <label>Email *</label>
                        <input type="email" name="email" value="{{ old('email', $usuario->email) }}" required>
                    </div>

                    <div class="form-group">
                        <label>Nueva contraseña <small style="color:#aaa;">(dejar vacío para no cambiar)</small></label>
                        <input type="password" name="password" minlength="8" autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label>Confirmar nueva contraseña</label>
                        <input type="password" name="password_confirmation">
                    </div>

                    <div class="form-group">
                        <label>Rol *</label>
                        <select name="role" required>
                            @foreach($roles as $rol)
                            <option value="{{ $rol->name }}"
                                {{ old('role', $usuario->roles->first()?->name ?? $usuario->role) == $rol->name ? 'selected' : '' }}>
                                {{ ucfirst($rol->name) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select name="activo">
                            <option value="1" {{ old('activo', $usuario->activo ? '1' : '0') == '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ old('activo', $usuario->activo ? '1' : '0') == '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:1rem;">
            <a href="{{ route('usuarios.index') }}" class="btn" style="background:#2a2d3e;color:#e2e8f0;padding:.5rem 1.5rem;text-decoration:none;border-radius:6px;">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="padding:.5rem 1.5rem;">Guardar Cambios</button>
        </div>
    </form>
</div>
@endsection
