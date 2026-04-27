{{-- Partial: matriz de permisos por módulo (Premium Edition) --}}
@php
$moduleLabels = [
    'vehiculos'     => 'Vehículos / Stock',
    'ventas'        => 'Ventas & Contratos',
    'clientes'      => 'Cartera de Clientes',
    'proveedores'   => 'Aliados / Proveedores',
    'repuestos'     => 'Inventario Repuestos',
    'facturas'      => 'Facturas de Compra',
    'cuotas'        => 'Planes de Pago',
    'finanzas'      => 'Cajas & Tesorería',
    'documentos'    => 'Gestor Documental',
    'reportes'      => 'Inteligencia de Negocios',
    'configuracion' => 'Ajustes de Empresa',
    'usuarios'      => 'Acceso de Staff',
    'roles'         => 'Políticas de Seguridad',
    'sifen'         => 'Sincronización SIFEN',
];
$actionLabels = [
    'ver'         => 'Lectura',
    'crear'       => 'Alta',
    'editar'      => 'Modifica',
    'eliminar'    => 'Baja',
    'pagar'       => 'Cobro',
    'reconciliar' => 'Cierre',
];
@endphp

<div class="overflow-hidden rounded-2xl border border-white/5 bg-black/20">
    <table class="erp-table !text-[0.65rem]">
        <thead>
            <tr class="!bg-white/5 text-muted-foreground uppercase tracking-widest">
                <th class="!pl-6 !py-4 font-black">Módulo de Sistema</th>
                @foreach($allActions as $action)
                    <th class="text-center !py-4 font-black">{{ $actionLabels[$action] ?? ucfirst($action) }}</th>
                @endforeach
                <th class="text-center !pr-6 font-black italic">Control</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
            @foreach($modules as $module => $actions)
                <tr class="hover:bg-white/5 transition-colors group">
                    <td class="!pl-6 !py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-1.5 h-1.5 rounded-full bg-primary/40 group-hover:bg-primary transition-colors"></div>
                            <span class="font-black text-white italic uppercase tracking-wider">{{ $moduleLabels[$module] ?? ucfirst($module) }}</span>
                        </div>
                    </td>
                    @foreach($allActions as $action)
                        <td class="text-center !py-4">
                            @if(in_array($action, $actions))
                                @php $permName = "{$module}.{$action}"; @endphp
                                <label class="relative inline-flex items-center cursor-pointer group/check">
                                    <input type="checkbox"
                                           name="permissions[]"
                                           value="{{ $permName }}"
                                           {{ $rolePerms->contains($permName) ? 'checked' : '' }}
                                           class="peer sr-only module-cb-{{ $module }}">
                                    <div class="w-5 h-5 bg-white/5 border border-white/10 rounded-md peer-checked:bg-primary peer-checked:border-primary transition-all flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path d="M5 13l4 4L19 7" /></svg>
                                    </div>
                                </label>
                            @else
                                <span class="text-white/5 font-black tracking-widest">—</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="text-center !pr-6">
                        <button type="button" class="select-module-btn px-3 py-1 rounded-lg bg-white/5 hover:bg-primary/20 text-[0.5rem] font-black uppercase text-white/40 hover:text-white transition-all border border-white/5" data-module="{{ $module }}">
                            Toggle
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
document.querySelectorAll('.select-module-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const mod = btn.dataset.module;
        const moduleCbs = document.querySelectorAll('.module-cb-' + mod);
        const allChecked = [...moduleCbs].every(c => c.checked);
        
        moduleCbs.forEach(c => c.checked = !allChecked);
        btn.classList.toggle('!bg-primary/40', !allChecked);
    });
});
</script>
