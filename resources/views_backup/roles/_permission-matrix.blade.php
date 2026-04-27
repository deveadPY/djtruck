{{-- Partial: matriz de permisos por módulo --}}
@php
$moduleLabels = [
    'vehiculos'     => 'Vehículos',
    'ventas'        => 'Ventas',
    'clientes'      => 'Clientes',
    'proveedores'   => 'Proveedores',
    'repuestos'     => 'Repuestos',
    'facturas'      => 'Facturas Proveedor',
    'cuotas'        => 'Cuotas / Planes',
    'finanzas'      => 'Finanzas / Cajas',
    'documentos'    => 'Documentos',
    'reportes'      => 'Reportes',
    'configuracion' => 'Configuración Empresa',
    'usuarios'      => 'Gestión de Usuarios',
    'roles'         => 'Gestión de Roles',
];
$actionLabels = [
    'ver'         => 'Ver',
    'crear'       => 'Crear',
    'editar'      => 'Editar',
    'eliminar'    => 'Eliminar',
    'pagar'       => 'Pagar',
    'reconciliar' => 'Reconciliar',
];
@endphp

<table style="width:100%;border-collapse:collapse;">
    <thead>
        <tr style="background:#1a1d2e;">
            <th style="padding:.7rem 1rem;text-align:left;color:#aaa;font-size:.78rem;font-weight:600;text-transform:uppercase;width:200px;">Módulo</th>
            @foreach($allActions as $action)
            <th style="padding:.7rem .5rem;text-align:center;color:#aaa;font-size:.78rem;font-weight:600;text-transform:uppercase;">{{ $actionLabels[$action] ?? ucfirst($action) }}</th>
            @endforeach
            <th style="padding:.7rem .5rem;text-align:center;color:#aaa;font-size:.78rem;font-weight:600;text-transform:uppercase;">Módulo</th>
        </tr>
    </thead>
    <tbody>
        @foreach($modules as $module => $actions)
        <tr style="border-bottom:1px solid #1e2130;" onmouseover="this.style.background='#1a1d2e'" onmouseout="this.style.background=''">
            <td style="padding:.7rem 1rem;font-weight:600;color:#e2e8f0;font-size:.9rem;">
                {{ $moduleLabels[$module] ?? ucfirst($module) }}
            </td>
            @foreach($allActions as $action)
            <td style="padding:.7rem .5rem;text-align:center;">
                @if(in_array($action, $actions))
                @php $permName = "{$module}.{$action}"; @endphp
                <input type="checkbox"
                       name="permissions[]"
                       value="{{ $permName }}"
                       {{ $rolePerms->contains($permName) ? 'checked' : '' }}
                       class="module-cb-{{ $module }}"
                       style="width:16px;height:16px;cursor:pointer;accent-color:#6c63ff;">
                @else
                <span style="color:#2a2d3e;">—</span>
                @endif
            </td>
            @endforeach
            <td style="padding:.7rem .5rem;text-align:center;">
                <label style="font-size:.75rem;color:#6c63ff;cursor:pointer;white-space:nowrap;">
                    <input type="checkbox" class="select-module" data-module="{{ $module }}" style="margin-right:.2rem;accent-color:#6c63ff;">
                    Todo
                </label>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<script>
document.querySelectorAll('.select-module').forEach(function(cb) {
    const mod = cb.dataset.module;
    const moduleCbs = document.querySelectorAll('.module-cb-' + mod);

    // Set initial state
    cb.checked = moduleCbs.length > 0 && [...moduleCbs].every(c => c.checked);

    cb.addEventListener('change', function() {
        moduleCbs.forEach(c => c.checked = this.checked);
    });

    moduleCbs.forEach(c => c.addEventListener('change', function() {
        cb.checked = [...moduleCbs].every(c => c.checked);
    }));
});
</script>
