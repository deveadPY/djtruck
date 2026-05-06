@extends('layouts.app')

@section('title', 'Editar Rol: ' . ucfirst($role->name))
@section('page-title', '✏️ Editar Rol')

@include('partials.form-styles')

@section('content')
<div style="max-width:900px;margin:0 auto;">

    @if(session('success'))
    <div class="flash-success" style="margin-bottom:1rem;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="flash-error" style="margin-bottom:1rem;">
        <ul style="margin:0;padding-left:1.2rem;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('roles.update', $role) }}">
        @csrf @method('PUT')

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header"><h3 style="margin:0;font-size:1rem;font-weight:600;">Datos del Rol</h3></div>
            <div class="card-body">
                <div class="form-group" style="max-width:300px;">
                    <label>Nombre del Rol *</label>
                    <input type="text" name="name" value="{{ old('name', $role->name) }}" required>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <h3 style="margin:0;font-size:1rem;font-weight:600;">Matriz de Permisos</h3>
                    <label style="font-size:.85rem;color:#aaa;cursor:pointer;">
                        <input type="checkbox" id="selectAll" style="margin-right:.3rem;"> Seleccionar todos
                    </label>
                </div>
            </div>
            <div class="card-body" style="padding:0;">
                @include('roles._permission-matrix', ['modules' => $modules, 'allActions' => $allActions, 'rolePerms' => $rolePerms])
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:1rem;">
            <a href="{{ route('roles.index') }}" class="btn" style="background:#2a2d3e;color:#e2e8f0;padding:.5rem 1.5rem;text-decoration:none;border-radius:6px;">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="padding:.5rem 1.5rem;">Guardar Cambios</button>
        </div>
    </form>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = this.checked);
});
// Init selectAll state
const cbs = document.querySelectorAll('input[name="permissions[]"]');
document.getElementById('selectAll').checked = cbs.length > 0 && [...cbs].every(c => c.checked);
</script>
@endsection
