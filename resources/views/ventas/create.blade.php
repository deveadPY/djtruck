@extends('layouts.app')
@section('title', 'Nueva Venta')
@section('page-title', 'Registrar Nueva Venta')

@section('content')
    @push('styles')
        <style>
            .step-content {
                display: none;
            }

            .step-content.active {
                display: block;
                animation: fadeIn 0.3s ease-in-out;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(5px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>
    @endpush

    @if($errors->any())
    <div class="flash-error">{{ $errors->first() }}</div>@endif

    <div style="margin-bottom:1rem"><a href="{{ route('ventas.index') }}" class="btn btn-ghost">← Volver</a></div>

    {{-- Step Indicator --}}
    <div
        class="flex flex-col sm:flex-row gap-2 sm:gap-0 bg-white dark:bg-slate-800 sm:border border-gray-200 dark:border-slate-700 sm:rounded-xl overflow-hidden mb-6 shadow-sm font-medium">
        <div class="step-item flex-1 text-center py-3 px-4 text-sm cursor-pointer transition-colors bg-primary text-white"
            id="nav-step-1" data-step="1" onclick="goToStep(1)">1. Vehículo</div>
        <div class="step-item flex-1 text-center py-3 px-4 text-sm cursor-pointer transition-colors text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700/50"
            id="nav-step-2" data-step="2" onclick="goToStep(2)">2. Cliente</div>
        <div class="step-item flex-1 text-center py-3 px-4 text-sm cursor-pointer transition-colors text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700/50"
            id="nav-step-3" data-step="3" onclick="goToStep(3)">3. Precio y Pago</div>
        <div class="step-item flex-1 text-center py-3 px-4 text-sm cursor-pointer transition-colors text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700/50"
            id="nav-step-4" data-step="4" onclick="goToStep(4)">4. Confirmar</div>
    </div>

    <form method="POST" action="{{ route('ventas.store') }}" id="ventaForm">
        @csrf
        {{-- ═══════════════════════════════════ STEP 1: ITEMS DE VENTA ═══════════════════════════════════ --}}
        <div class="step-content active" id="step1">
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 w-full">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-bold">Items de Venta</h2>
                        </div>
                        
                        {{-- Tabs --}}
                        <div class="flex bg-slate-100 dark:bg-slate-900 p-1 rounded-lg">
                            <button type="button" onclick="switchItemTab('camiones')" id="tab-camiones" class="px-4 py-1.5 text-xs font-bold rounded-md transition-all bg-white dark:bg-slate-800 shadow-sm text-primary">CAMIONES</button>
                            <button type="button" onclick="switchItemTab('repuestos')" id="tab-repuestos" class="px-4 py-1.5 text-xs font-bold rounded-md transition-all text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">REPUESTOS</button>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    
                    {{-- SEARCH BARS --}}
                    <div class="mb-6 flex flex-col md:flex-row gap-4">
                        <div class="relative flex-1" id="search-camiones-container">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <input type="text" id="buscarVehiculo" class="form-input pl-10 w-full" placeholder="Buscar por marca, modelo o chasis...">
                        </div>
                        <div class="relative flex-1 hidden" id="search-repuestos-container">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <input type="text" id="buscarRepuesto" class="form-input pl-10 w-full" placeholder="Buscar por código o descripción...">
                        </div>
                    </div>

                    {{-- VEHICLES GRID --}}
                    <div id="item-grid-camiones" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 transition-all">
                        @foreach($vehiculos as $v)
                            @php
                                $badgeColor = match ($v->estado) {
                                    'DISPONIBLE' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/30',
                                    'RESERVADO' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border-amber-200 dark:border-amber-800/30',
                                    default => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 border-blue-200 dark:border-blue-800/30'
                                };
                            @endphp
                            <div class="vehicle-card group relative flex flex-col justify-between p-4 cursor-pointer bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl transition-all hover:border-primary hover:shadow-md [&.selected]:ring-2 [&.selected]:ring-primary [&.selected]:border-primary [&.selected]:bg-indigo-50/50 dark:[&.selected]:bg-indigo-900/10 shadow-sm"
                                data-id="{{ $v->id }}" 
                                data-costo="{{ $v->costo_origen_usd + ($v->total_gastos_usd ?? 0) }}"
                                data-precio-sugerido="{{ $v->precio_venta_sugerido_usd ?? 0 }}"
                                data-precio-contado="{{ $v->precio_contado_usd ?? 0 }}"
                                data-precio-cuotas="{{ $v->precio_cuotas_usd ?? 0 }}"
                                data-marca="{{ $v->marca }}"
                                data-modelo="{{ $v->modelo }}" data-chasis="{{ $v->numero_chasis }}" data-anio="{{ $v->anio }}"
                                data-estado="{{ $v->estado }}" data-color="{{ $v->color }}" data-km="{{ $v->kilometraje }}"
                                onclick="addItemToVenta('camion', this)">

                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <div class="font-bold text-sm text-slate-800 dark:text-slate-100 group-hover:text-primary transition-colors">
                                            {{ $v->marca }} {{ $v->modelo }}
                                        </div>
                                        <div class="text-[0.65rem] text-slate-500 dark:text-slate-400 mt-0.5 tracking-wider">{{ $v->anio }} · {{ $v->color ?? 'N/D' }}</div>
                                    </div>
                                    <span class="px-2 py-0.5 border rounded-full text-[0.55rem] font-bold tracking-wider {{ $badgeColor }} uppercase">{{ $v->estado }}</span>
                                </div>

                                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-lg p-2.5 mb-3 border border-slate-100 dark:border-slate-700/50">
                                    <span class="text-[0.6rem] text-slate-500 dark:text-slate-400 block uppercase font-bold mb-0.5">Chasis</span>
                                    <strong class="text-xs font-mono text-slate-700 dark:text-slate-300">{{ $v->numero_chasis }}</strong>
                                </div>

                                <div class="flex justify-between items-end pt-3 border-t border-gray-100 dark:border-slate-700/70 mt-auto">
                                    <div class="flex flex-col">
                                        @if(($v->precio_contado_usd ?? 0) > 0)
                                            <span class="text-emerald-600 dark:text-emerald-400 font-bold text-[0.7rem]" title="Precio Contado">CONT: US$ {{ number_format($v->precio_contado_usd, 0, ',', '.') }}</span>
                                        @endif
                                        @if(($v->precio_cuotas_usd ?? 0) > 0)
                                            <span class="text-indigo-500 dark:text-indigo-400 font-bold text-[0.7rem]" title="Precio Cuotas">CUOT: US$ {{ number_format($v->precio_cuotas_usd, 0, ',', '.') }}</span>
                                        @endif
                                    </div>
                                    <button type="button" class="p-1.5 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-all shadow-sm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- REPUESTOS GRID --}}
                    <div id="item-grid-repuestos" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 hidden transition-all">
                        @foreach($repuestos ?? [] as $r)
                            <div class="repuesto-card group relative flex flex-col justify-between p-4 cursor-pointer bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl transition-all hover:border-primary hover:shadow-md shadow-sm"
                                data-id="{{ $r->id }}" 
                                data-codigo="{{ $r->codigo }}"
                                data-descripcion="{{ $r->descripcion }}"
                                data-precio-usd="{{ $r->precio_venta_usd ?? 0 }}"
                                data-costo-usd="{{ $r->costo_promedio_usd }}"
                                data-stock="{{ $r->stock_actual }}"
                                onclick="addItemToVenta('repuesto', this)">

                                <div class="flex justify-between items-start mb-3">
                                    <div class="min-w-0">
                                        <div class="font-bold text-xs text-slate-800 dark:text-slate-100 group-hover:text-primary transition-colors truncate" title="{{ $r->descripcion }}">
                                            {{ $r->descripcion }}
                                        </div>
                                        <div class="text-[0.65rem] font-mono text-slate-500 dark:text-slate-400 mt-0.5 tracking-tight">{{ $r->codigo }}</div>
                                    </div>
                                    <span class="px-2 py-0.5 border rounded-full text-[0.55rem] font-bold tracking-wider bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300 uppercase">{{ $r->unidad_medida }}</span>
                                </div>

                                <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-900/50 rounded-lg p-2.5 mb-3 border border-slate-100 dark:border-slate-700/50">
                                    <div class="flex flex-col">
                                        <span class="text-[0.6rem] text-slate-500 dark:text-slate-400 uppercase font-bold">Stock</span>
                                        <strong class="text-xs {{ $r->stock_actual <= $r->stock_minimo ? 'text-amber-500' : 'text-slate-700 dark:text-slate-300' }}">{{ number_format($r->stock_actual, 2) }}</strong>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[0.6rem] text-slate-500 dark:text-slate-400 uppercase font-bold">Precio</span>
                                        <div class="text-sm font-bold text-primary">US$ {{ number_format($r->precio_venta_usd, 2) }}</div>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-2 pt-1 border-t border-gray-100 dark:border-slate-700/70 mt-auto">
                                    <button type="button" class="p-1.5 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-all shadow-sm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- SHOPPING CART / SELECTED ITEMS --}}
                    <div class="mt-8 pt-8 border-t border-slate-200 dark:border-slate-700">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold uppercase tracking-widest text-slate-500 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                Items Seleccionados
                            </h3>
                            <div class="text-xs text-slate-400" id="cart-count">0 items añadidos</div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm border-separate border-spacing-y-2">
                                <thead class="text-[0.65rem] font-bold text-slate-400 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-4 py-2">Descripción</th>
                                        <th class="px-4 py-2 text-center">Cant.</th>
                                        <th class="px-4 py-2 text-right">Precio Unit. (USD)</th>
                                        <th class="px-4 py-2 text-right">Subtotal (USD)</th>
                                        <th class="px-4 py-2 text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="cart-body">
                                    <tr id="cart-empty-row">
                                        <td colspan="5" class="px-4 py-12 text-center text-slate-400 italic bg-slate-50/50 dark:bg-slate-900/30 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700">
                                            No se han seleccionado ítems aún. Selecciona un camión o repuestos arriba.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex flex-col items-end">
                            <div class="flex items-center gap-8 py-4 px-6 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm">
                                <div class="text-right">
                                    <span class="text-[0.6rem] font-bold text-slate-500 uppercase tracking-widest block mb-1">Total de Venta</span>
                                    <div class="text-2xl font-black text-primary" id="cart-total-usd">US$ 0,00</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-8">
                        <input type="hidden" name="vehiculo_id" id="vehiculo_id_input" value="{{ old('vehiculo_id') }}">
                        <button type="button" class="btn btn-primary px-8" onclick="goToStep(2)" id="btnStep1Next" disabled>
                            Siguiente → Cliente
                            <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════ STEP 2: CLIENTE ═══════════════════════════════════ --}}
        <div class="step-content" id="step2">
            <div class="erp-card">
                <div class="erp-card-header">
                    <h2>Seleccionar Cliente</h2>
                    <button type="button" class="btn btn-ghost" onclick="abrirModalCliente()" style="font-size:.8rem">+
                        Nuevo Cliente</button>
                </div>
                <div class="erp-card-body">
                    <input type="hidden" name="cliente_id" id="cliente_id_input" value="{{ old('cliente_id') }}" required>

                    <div style="margin-bottom:1.5rem">
                        <input type="text" id="buscarCliente" class="form-input w-full md:w-1/2 lg:w-1/3"
                            placeholder="🔍 Buscar por nombre, RUC...">
                    </div>

                    <div id="clientesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach($clientes as $c)
                            <div class="client-card group p-4 py-5 cursor-pointer bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl transition-all hover:border-primary hover:shadow-md [&.selected]:ring-2 [&.selected]:ring-primary [&.selected]:border-primary [&.selected]:bg-indigo-50/50 dark:[&.selected]:bg-indigo-900/10 shadow-sm"
                                data-id="{{ $c->id }}" data-nombre="{{ $c->razon_social }}" data-ruc="{{ $c->ruc }}"
                                data-telefono="{{ $c->telefono }}" data-pais="{{ $c->pais }}"
                                onclick="seleccionarCliente(this)">

                                <div class="flex items-start gap-3 mb-2">
                                    <div
                                        class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-slate-500 dark:text-slate-400 flex-shrink-0 border border-slate-200 dark:border-slate-700 group-[.selected]:bg-primary/10 group-[.selected]:text-primary group-[.selected]:border-primary/30 transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-[1.05rem] text-slate-800 dark:text-slate-100 group-[.selected]:text-primary dark:group-[.selected]:text-indigo-400 transition-colors truncate"
                                            title="{{ $c->razon_social }}">{{ $c->razon_social }}</div>
                                        <div class="text-[0.75rem] text-slate-500 dark:text-slate-400 mt-0.5 font-mono">RUC:
                                            {{ $c->ruc ?: 'N/A' }}
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="mt-4 pt-3 border-t border-gray-100 dark:border-slate-700/70 flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-500 dark:text-slate-400">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                        </svg>
                                        <span>{{ $c->pais }}</span>
                                    </div>
                                    @if($c->telefono)
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-2.896-1.596-5.48-4.18-7.076-7.076l1.293-.97c.362-.271.527-.733.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                            </svg>
                                            <span>{{ $c->telefono }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div style="display:flex;justify-content:space-between;margin-top:1.5rem">
                        <button type="button" class="btn btn-ghost" onclick="goToStep(1)">← Vehículo</button>
                        <button type="button" class="btn btn-primary" onclick="goToStep(3)" id="btnStep2Next"
                            disabled>Siguiente → Pago</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════ STEP 3: PRECIO Y PAGO ═══════════════════════════════════ --}}
        <div class="step-content" id="step3">

            {{-- Modalidad selector (prominent, top of step) --}}
            <div class="erp-card" style="margin-bottom:1.25rem">
                <div class="erp-card-header">
                    <h2>Modalidad de Venta</h2>
                </div>
                <div class="erp-card-body">
                    <input type="hidden" name="modalidad_pago" id="modalidad_pago" value="CONTADO">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <button type="button" id="btn-modalidad-contado"
                            onclick="setPaymentMode('CONTADO')"
                            class="flex items-center gap-4 p-5 rounded-xl border-2 border-primary bg-indigo-50/60 dark:bg-indigo-900/20 text-left transition-all hover:shadow-md ring-2 ring-primary">
                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-slate-800 dark:text-slate-100 text-base">Contado</div>
                                <div class="text-sm text-slate-500 dark:text-slate-400 mt-0.5" id="label-precio-contado">Pago total en una sola operación</div>
                            </div>
                            <div id="check-contado" class="w-5 h-5 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                                <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </div>
                        </button>

                        <button type="button" id="btn-modalidad-cuotas"
                            onclick="setPaymentMode('CUOTAS')"
                            class="flex items-center gap-4 p-5 rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-left transition-all hover:border-primary hover:shadow-md">
                            <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-900 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-slate-800 dark:text-slate-100 text-base">Plan de Cuotas</div>
                                <div class="text-sm text-slate-500 dark:text-slate-400 mt-0.5" id="label-precio-cuotas">Financiamiento en cuotas mensuales</div>
                            </div>
                            <div id="check-cuotas" class="w-5 h-5 rounded-full border-2 border-slate-300 dark:border-slate-600 flex-shrink-0 hidden"></div>
                        </button>
                    </div>
                </div>
            </div>

            <div class="erp-card" style="margin-bottom:1.25rem">
                <div class="erp-card-header">
                    <h2>Precio de Venta</h2>
                </div>
                <div class="erp-card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                        <div class="form-group flex flex-col gap-1.5">
                            <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Fecha de
                                venta <span class="text-red-500">*</span></label>
                            <input type="date" name="fecha_venta" class="form-input"
                                value="{{ old('fecha_venta', date('Y-m-d')) }}" required>
                        </div>
                        <div class="form-group flex flex-col gap-1.5">
                            <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Moneda de
                                venta <span class="text-red-500">*</span></label>
                            <select name="moneda_venta" class="form-input" required onchange="calcularTotalVentaUsd()">
                                @foreach(['USD', 'PYG', 'BRL'] as $m)
                                    <option value="{{ $m }}" {{ old('moneda_venta', 'USD') == $m ? 'selected' : '' }}>{{ $m }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group flex flex-col gap-1.5">
                            <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Precio en
                                moneda <span class="text-red-500">*</span></label>
                            <input type="number" name="precio_venta_moneda" class="form-input"
                                value="{{ old('precio_venta_moneda') }}" step="0.01" min="0" required id="precio_moneda"
                                oninput="calcularTotalVentaUsd()">
                        </div>
                        <div class="form-group flex flex-col gap-1.5">
                            <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Precio en USD
                                <span class="text-red-500">*</span></label>
                            <input type="number" name="precio_venta_usd" class="form-input"
                                value="{{ old('precio_venta_usd') }}" step="0.01" min="0" required id="precio_usd"
                                oninput="calcRent()">
                        </div>
                        <div class="form-group flex flex-col gap-1.5">
                            <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Descuento
                                (moneda)</label>
                            <input type="number" name="descuento_moneda" class="form-input"
                                value="{{ old('descuento_moneda', 0) }}" step="0.01" min="0" id="descuento_moneda"
                                oninput="calcularTotalVentaUsd()">
                        </div>
                        <div class="form-group flex flex-col gap-1.5 lg:col-span-3">
                            <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Descuento
                                (USD)</label>
                            <input type="number" name="descuento_usd" class="form-input lg:w-1/3"
                                value="{{ old('descuento_usd', 0) }}" step="0.01" min="0" id="descuento_usd"
                                oninput="calcRent()">
                        </div>
                        <input type="hidden" name="tasa_cambio_venta" value="{{ old('tasa_cambio_venta', 1) }}">
                        <input type="hidden" name="estado" value="COMPLETADO">
                    </div>

                    <div
                        class="bg-indigo-50/50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800/50 rounded-xl p-5 mt-6 flex justify-between items-center shadow-sm">
                        <span class="text-sm font-bold text-indigo-700 dark:text-indigo-400 tracking-wider uppercase">Precio
                            Final (USD)</span>
                        <strong class="text-2xl text-indigo-700 dark:text-indigo-400 font-extrabold"
                            id="precio_final_display">$ 0.00</strong>
                    </div>

                    {{-- Valor libro info --}}
                    <div id="valor_libro_info"
                        class="mt-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 px-4 py-3 rounded-lg text-sm text-slate-600 dark:text-slate-300 flex items-center gap-3"
                        style="display:none;">
                        <span class="font-medium">Valor libro:</span> <strong class="text-indigo-600 dark:text-indigo-400"
                            id="valor_libro_span">—</strong> USD
                        <span class="mx-1 text-slate-300 dark:text-slate-600">|</span>
                        <span class="font-medium">Rentabilidad:</span> <strong id="rent_span">—</strong>
                    </div>
                </div>
            </div>

            {{-- Mode toggle: Contado vs Cuotas --}}
            <div class="erp-card" style="margin-bottom:1.25rem">
                <div class="erp-card-header">
                    <h2>Pagos</h2>
                </div>
                <div class="erp-card-body">
                    <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:1rem" id="texto_pagos">
                        Registrá los pagos recibidos al contado.
                    </div>

                    <div id="pagos_container">
                        {{-- First payment row (always visible) --}}
                        <div class="payment-entry" id="pago_row_0">
                            <div
                                class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700/50 mb-3">
                                <div class="form-group flex flex-col gap-1.5">
                                    <label
                                        class="form-label text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Tipo
                                        de pago</label>
                                    <select name="pagos[0][tipo]" class="form-input"
                                        onchange="toggleCamposPago(0, this.value)">
                                        <option value="EFECTIVO">Efectivo</option>
                                        <option value="TRANSFERENCIA">Transferencia</option>
                                        <option value="VEHICULO_CANJE">Vehículo parte de pago</option>
                                        <option value="CHEQUE">Cheque</option>
                                    </select>
                                </div>
                                <div class="form-group flex flex-col gap-1.5">
                                    <label
                                        class="form-label text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Monto
                                        USD</label>
                                    <input type="number" name="pagos[0][monto_usd]" step="0.01" min="0"
                                        class="form-input pago-monto" oninput="calcularTotalPagos()">
                                </div>
                                <div class="form-group flex-col gap-1.5" id="ref_container_0" style="display:none">
                                    <label
                                        class="form-label text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Referencia
                                        / Banco</label>
                                    <input type="text" name="pagos[0][referencia]" class="form-input"
                                        placeholder="Nro. transferencia...">
                                </div>
                                <div class="form-group flex-col gap-1.5" id="canje_container_0" style="display:none">
                                    <label
                                        class="form-label text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Vehículo
                                        en Canje</label>
                                    <select name="pagos[0][vehiculo_canje_id]" class="form-input">
                                        <option value="">— Seleccionar —</option>
                                        @foreach($vehiculos_canje ?? [] as $vc)
                                            <option value="{{ $vc->id }}">{{ $vc->marca }} {{ $vc->modelo }} —
                                                {{ $vc->numero_chasis }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button"
                        class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors mb-4 flex items-center gap-1"
                        onclick="agregarPago()">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Agregar otro pago / entrega
                    </button>

                    <div
                        class="bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 rounded-xl p-5 flex justify-between items-center mb-5 shadow-sm">
                        <div>
                            <div class="text-[0.72rem] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider mb-1"
                                id="label_total_pagos">Total pagos registrados</div>
                            <div class="text-xl font-extrabold text-slate-800 dark:text-slate-100" id="total_pagos_display">
                                $
                                0,00</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[0.72rem] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider mb-1"
                                id="label_saldo">
                                Saldo pendiente</div>
                            <div class="text-xl font-extrabold" id="saldo_pendiente_display">$ 0,00</div>
                        </div>
                    </div>

                    {{-- === PLAN DE CUOTAS CONFIG === --}}
                    <div id="seccion_plan_cuotas" class="hidden pt-6 mt-2 border-t border-slate-200 dark:border-slate-700">
                        <h3 class="mt-0 mb-5 text-lg font-bold text-slate-800 dark:text-slate-100">Configuración del Plan de
                            Cuotas</h3>

                        <div
                            class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 rounded-lg p-4 mb-6 text-sm flex gap-6 text-slate-600 dark:text-slate-300">
                            <div>Precio Base: <strong class="text-slate-800 dark:text-white"
                                    id="plan_precio_base">—</strong></div>
                            <div class="text-red-500 dark:text-red-400 font-medium">Descuento: <strong
                                    id="plan_descuento">—</strong></div>
                            <div class="text-indigo-600 dark:text-indigo-400 font-medium">Precio Final: <strong
                                    id="plan_precio_final">—</strong></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div class="form-group flex flex-col gap-1.5">
                                <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Tipo de
                                    plan <span class="text-red-500">*</span></label>
                                <select name="tipo_plan" id="tipo_plan" class="form-input" onchange="onTipoPlanChange()">
                                    <option value="FRANCESA">Francesa (cuota fija)</option>
                                    <option value="ALEMANA">Alemana (capital fijo)</option>
                                    <option value="MANUAL" selected>Manual / Personalizado</option>
                                </select>
                            </div>
                            <div class="form-group flex flex-col gap-1.5">
                                <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Capital a
                                    financiar (USD)</label>
                                <input type="number" id="capital_total_usd_visual"
                                    class="form-input bg-slate-50 dark:bg-slate-800 text-slate-500 italic" step="0.01"
                                    min="0" readonly>
                                <input type="hidden" name="capital_total_usd" id="capital_usd_input">
                            </div>
                        </div>

                        <div id="auto-config"
                            class="hidden mt-6 bg-slate-50 dark:bg-slate-800/50 p-5 rounded-xl border border-slate-100 dark:border-slate-700/50">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div class="form-group flex flex-col gap-1.5">
                                    <label
                                        class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Número
                                        de cuotas <span class="text-red-500">*</span></label>
                                    <input type="number" name="numero_cuotas" id="numero_cuotas" class="form-input"
                                        value="12" min="1" max="120">
                                </div>
                                <div class="form-group flex flex-col gap-1.5">
                                    <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Tasa
                                        de interés mensual (%)</label>
                                    <input type="number" name="tasa_interes_mensual" class="form-input" value="0"
                                        step="0.01" min="0">
                                </div>
                                <div class="form-group flex flex-col gap-1.5">
                                    <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Fecha
                                        primera cuota <span class="text-red-500">*</span></label>
                                    <input type="date" name="fecha_primera_cuota" class="form-input"
                                        value="{{ \Carbon\Carbon::now()->addMonth()->format('Y-m-d') }}">
                                </div>
                                <div class="form-group flex flex-col gap-1.5">
                                    <label
                                        class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Refuerzo
                                        cada N meses</label>
                                    <input type="number" name="refuerzo_cada" id="refuerzo_cada" class="form-input" min="0"
                                        max="12" value="0" placeholder="0 = sin refuerzo">
                                </div>
                                <div class="form-group flex flex-col gap-1.5">
                                    <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Monto
                                        del refuerzo (USD)</label>
                                    <input type="number" name="refuerzo_monto" id="refuerzo_monto" class="form-input"
                                        step="0.01" min="0" value="0">
                                </div>
                            </div>
                        </div>

                        <div id="manual-config" class="mt-6">
                            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                                Configura filas de cuotas manualmente. Puedes mezclar cuotas regulares y refuerzos.
                            </p>
                            <div
                                class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 grid grid-cols-[30px_1fr_1fr_1fr_auto] gap-2 px-2">
                                <span>#</span><span>Fecha Vencimiento</span><span>Tipo</span><span>Monto
                                    (USD)</span><span></span>
                            </div>
                            <div id="cuotas-container" class="flex flex-col gap-2"></div>
                            <div class="flex gap-3 mt-4">
                                <button type="button"
                                    class="text-sm font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 px-4 py-2 rounded-lg transition-colors border border-slate-200 dark:border-slate-700"
                                    onclick="addCuotaManual('REGULAR')">
                                    + Cuota Regular
                                </button>
                                <button type="button"
                                    class="text-sm font-semibold bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-500 hover:bg-amber-100 dark:hover:bg-amber-900/40 px-4 py-2 rounded-lg transition-colors border border-amber-200 dark:border-amber-800/50"
                                    onclick="addCuotaManual('REFUERZO')">
                                    + Cuota Refuerzo
                                </button>
                            </div>
                            <div
                                class="bg-indigo-50/50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/40 rounded-xl p-4 flex flex-col sm:flex-row justify-between items-center mt-6 shadow-sm">
                                <div class="text-sm text-indigo-900 dark:text-indigo-200">Total Cuotas: <strong
                                        id="total_cuotas_sum"
                                        class="text-lg text-indigo-700 dark:text-indigo-400 font-extrabold ml-1">$
                                        0.00</strong></div>
                                <div class="text-sm text-slate-500 dark:text-slate-400 mt-2 sm:mt-0">Diferencia VS Capital:
                                    <strong id="diferencia_cuotas" class="font-bold ml-1">$ 0.00</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group flex flex-col gap-1.5 mb-6">
                <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Observaciones</label>
                <textarea name="observaciones" rows="2" class="form-input">{{ old('observaciones') }}</textarea>
            </div>

            <div class="flex justify-between items-center mt-6">
                <button type="button"
                    class="px-5 py-2.5 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    onclick="goToStep(2)">← Volver a Cliente</button>
                <button type="button"
                    class="px-6 py-2.5 rounded-lg text-sm font-medium bg-primary text-white hover:bg-indigo-700 shadow-sm transition-colors"
                    onclick="goToStep(4)">Siguiente → Confirmar</button>
            </div>
        </div>

        {{-- ═══════════════════════════════════ STEP 4: CONFIRMACIÓN ═══════════════════════════════════ --}}
        <div class="step-content" id="step4">
            <div class="erp-card">
                <div class="erp-card-header">
                    <h2>Resumen de la Venta</h2>
                </div>
                <div class="erp-card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                        <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-5 md:col-span-1">
                            <div class="text-[0.7rem] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                Items de la Venta
                            </div>
                            <div id="resumen_items_list" class="space-y-3 max-h-[150px] overflow-y-auto pr-2 custom-scrollbar">
                                {{-- Se llena vía JS --}}
                            </div>
                        </div>
                        <div
                            class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-5">
                            <div
                                class="text-[0.7rem] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                                Cliente
                            </div>
                            <div class="font-bold text-lg text-slate-800 dark:text-slate-100" id="resumen_cliente">—</div>
                            <div class="text-sm text-slate-500 dark:text-slate-400 mt-1" id="resumen_cliente_ruc">—</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div
                            class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-4 text-center">
                            <div class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                Precio</div>
                            <div class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400" id="resumen_precio">$0
                            </div>
                        </div>
                        <div
                            class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-4 text-center">
                            <div class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                Modalidad</div>
                            <div class="text-xl font-bold text-slate-800 dark:text-slate-100" id="resumen_modalidad">Contado
                            </div>
                        </div>
                        <div
                            class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-4 text-center">
                            <div class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                Rentabilidad</div>
                            <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400" id="resumen_rentabilidad">
                                $0</div>
                        </div>
                    </div>

                    <div id="resumen_pagos_box" class="mb-6"></div>

                    <div class="flex justify-between items-center mt-8">
                        <button type="button"
                            class="px-5 py-2.5 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            onclick="goToStep(3)">← Volver a editar</button>
                        <button type="button" onclick="abrirModalConfirmacion()"
                            class="px-8 py-3 rounded-xl text-base font-bold bg-primary text-white hover:bg-indigo-700 shadow-sm transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            Confirmar y Registrar Venta</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- ═══════ Modal Nuevo Cliente ═══════ --}}
    <div id="modalCliente"
        class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div
            class="bg-white dark:bg-slate-800 w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[90vh]">
            <div
                class="px-6 py-4 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/80">
                <h3 class="m-0 text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    Nuevo Cliente
                </h3>
                <button type="button" onclick="cerrarModalCliente()"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto">
                <form id="formModalCliente" class="flex flex-col gap-5">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group flex flex-col gap-1.5 md:col-span-2">
                            <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Razón Social
                                <span class="text-red-500">*</span></label>
                            <input type="text" name="razon_social" class="form-input" required>
                        </div>
                        <div class="form-group flex flex-col gap-1.5">
                            <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">RUC /
                                CI</label>
                            <input type="text" name="ruc" class="form-input">
                        </div>
                        <div class="form-group flex flex-col gap-1.5">
                            <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">País <span
                                    class="text-red-500">*</span></label>
                            <select name="pais" class="form-input" required>
                                <option value="PY" selected>Paraguay (PY)</option>
                                <option value="BR">Brasil (BR)</option>
                                <option value="AR">Argentina (AR)</option>
                                <option value="BO">Bolivia (BO)</option>
                            </select>
                        </div>
                        <div class="form-group flex flex-col gap-1.5">
                            <label
                                class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Teléfono</label>
                            <input type="text" name="telefono" class="form-input">
                        </div>
                        <div class="form-group flex flex-col gap-1.5">
                            <label class="form-label text-sm font-semibold text-slate-700 dark:text-slate-300">Línea de
                                Crédito (USD)</label>
                            <input type="number" name="linea_credito_usd" class="form-input" value="0" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/50">
                        <button type="button"
                            class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            onclick="cerrarModalCliente()">Cancelar</button>
                        <button type="submit"
                            class="px-5 py-2 rounded-lg text-sm font-medium bg-primary text-white hover:bg-indigo-700 shadow-sm transition-colors">Guardar
                            Cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════ Modal Confirmación de Venta (Doble Factor) ═══════ --}}
    <div id="modalConfirmacion"
        class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        style="display:none;">
        <div class="bg-white dark:bg-slate-800 w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-slate-200 dark:border-slate-700">
            {{-- Header --}}
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/80 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">Confirmar Registro de Venta</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Verificá los datos antes de confirmar</p>
                </div>
            </div>

            {{-- Body: Validation Checklist --}}
            <div class="p-6 overflow-y-auto space-y-3" id="confirmacion-body">
                {{-- Filled by JS --}}
            </div>

            {{-- Warnings --}}
            <div id="confirmacion-warnings" class="px-6 pb-3 hidden">
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-xl p-4 text-sm text-amber-800 dark:text-amber-300">
                    <div class="flex items-center gap-2 mb-2 font-bold text-amber-700 dark:text-amber-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                        Advertencias
                    </div>
                    <ul id="confirmacion-warnings-list" class="space-y-1 text-xs list-disc pl-4"></ul>
                </div>
            </div>

            {{-- Errors --}}
            <div id="confirmacion-errors" class="px-6 pb-3 hidden">
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 rounded-xl p-4 text-sm text-red-800 dark:text-red-300">
                    <div class="flex items-center gap-2 mb-2 font-bold text-red-700 dark:text-red-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        Errores que impiden registrar
                    </div>
                    <ul id="confirmacion-errors-list" class="space-y-1 text-xs list-disc pl-4"></ul>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex justify-between items-center gap-3">
                <button type="button" onclick="cerrarModalConfirmacion()"
                    class="px-5 py-2.5 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    Cancelar
                </button>
                <button type="button" id="btnConfirmarFinal" onclick="confirmarYRegistrar()"
                    class="px-6 py-2.5 rounded-xl text-sm font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-lg shadow-emerald-600/25 transition-all flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none"
                    disabled>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    Registrar Venta
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            let currentStep = 1;
            let selectedVehiculo = null;
            let selectedCliente = null;
            let pagoCount = 1;
            let cuotaIdx = 0;
            let currentRates = { PYG: 1, BRL: 1 };

            // ══════════ STEP NAVIGATION ══════════
            function goToStep(step) {
                if (step === 2 && itemsVenta.length === 0) {
                    alert('Agregá al menos un ítem al carrito primero.'); return;
                }
                if (step === 3 && !document.getElementById('cliente_id_input').value) {
                    alert('Seleccioná un cliente primero.'); return;
                }
                if (step === 4) buildResumen();

                document.querySelectorAll('.step-content').forEach(s => s.classList.remove('active'));
                document.getElementById('step' + step).classList.add('active');

                document.querySelectorAll('.step-item').forEach(s => {
                    const n = parseInt(s.dataset.step);
                    s.classList.remove('bg-primary', 'text-white', 'text-slate-500', 'dark:text-slate-400', 'text-primary', 'bg-primary/10', 'dark:bg-primary/20');
                    if (n === step) {
                        s.classList.add('bg-primary', 'text-white');
                    } else if (n < step) {
                        s.classList.add('text-primary', 'bg-primary/10', 'dark:bg-primary/20');
                    } else {
                        s.classList.add('text-slate-500', 'dark:text-slate-400');
                    }
                });
                currentStep = step;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            // ══════════ VEHICLE AND CLIENT ══════════
            function seleccionarVehiculo(el) {
                document.querySelectorAll('#vehiculosGrid .vehicle-card').forEach(c => c.classList.remove('selected'));
                el.classList.add('selected');
                document.getElementById('vehiculo_id_input').value = el.dataset.id;
                document.getElementById('btnStep1Next').disabled = false;
                selectedVehiculo = {
                    id: el.dataset.id, marca: el.dataset.marca, modelo: el.dataset.modelo,
                    chasis: el.dataset.chasis, año: el.dataset.anio, costo: parseFloat(el.dataset.costo),
                    precioSugerido: parseFloat(el.dataset.precioSugerido) || 0,
                    precioContado: parseFloat(el.dataset.precioContado) || 0,
                    precioCuotas: parseFloat(el.dataset.precioCuotas) || 0,
                };

                // Update price labels on modalidad buttons
                const labelContado = document.getElementById('label-precio-contado');
                const labelCuotas  = document.getElementById('label-precio-cuotas');
                if (labelContado) labelContado.textContent = selectedVehiculo.precioContado > 0
                    ? 'US$ ' + formatNumber(selectedVehiculo.precioContado) : 'Pago total en una sola operación';
                if (labelCuotas)  labelCuotas.textContent  = selectedVehiculo.precioCuotas  > 0
                    ? 'US$ ' + formatNumber(selectedVehiculo.precioCuotas)  : 'Financiamiento en cuotas mensuales';

                const spanCosto = document.getElementById('valor_libro_span');
                spanCosto.dataset.costoRaw = selectedVehiculo.costo;
                spanCosto.textContent = '$ ' + formatNumber(selectedVehiculo.costo);
                document.getElementById('valor_libro_info').style.display = 'block';

                // Auto-fill price based on current modalidad
                _autoFillPrecioFromModalidad();
            }

            function _autoFillPrecioFromModalidad() {
                if (!selectedVehiculo) return;
                const modalidad = document.getElementById('modalidad_pago').value;
                let precio = modalidad === 'CONTADO' ? selectedVehiculo.precioContado : selectedVehiculo.precioCuotas;
                if (precio <= 0) precio = selectedVehiculo.precioSugerido; // fallback

                // Update the camion item price in the cart
                const camionItem = itemsVenta.find(i => i.type === 'camion');
                if (camionItem && precio > 0) {
                    camionItem.precio_unitario_usd = precio;
                    renderCart(); // Re-render cart with new price (this also updates Step 3 inputs)
                } else if (precio > 0) {
                    const selectMoneda = document.querySelector('select[name="moneda_venta"]');
                    if (selectMoneda) selectMoneda.value = 'USD';
                    const inputMoneda = document.querySelector('input[name="precio_venta_moneda"]');
                    if (inputMoneda) {
                        inputMoneda.value = precio.toFixed(2);
                        inputMoneda.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                } else {
                    calcRent();
                }
            }

            document.getElementById('buscarVehiculo').addEventListener('input', function () {
                const term = this.value.toLowerCase();
                document.querySelectorAll('#item-grid-camiones .vehicle-card').forEach(card => {
                    const text = (card.dataset.marca + ' ' + card.dataset.modelo + ' ' + card.dataset.chasis).toLowerCase();
                    card.style.display = text.includes(term) ? '' : 'none';
                });
            });

            document.getElementById('buscarRepuesto').addEventListener('input', function () {
                const term = this.value.toLowerCase();
                document.querySelectorAll('#item-grid-repuestos .repuesto-card').forEach(card => {
                    const text = (card.dataset.codigo + ' ' + card.dataset.descripcion).toLowerCase();
                    card.style.display = text.includes(term) ? '' : 'none';
                });
            });

            function seleccionarCliente(el) {
                document.querySelectorAll('#clientesGrid .client-card').forEach(c => c.classList.remove('selected'));
                el.classList.add('selected');
                document.getElementById('cliente_id_input').value = el.dataset.id;
                document.getElementById('btnStep2Next').disabled = false;
                selectedCliente = {
                    id: el.dataset.id, nombre: el.dataset.nombre, ruc: el.dataset.ruc,
                    telefono: el.dataset.telefono, pais: el.dataset.pais
                };
            }

            document.getElementById('buscarCliente').addEventListener('input', function () {
                const term = this.value.toLowerCase();
                document.querySelectorAll('#clientesGrid .client-card').forEach(card => {
                    const text = (card.dataset.nombre + ' ' + card.dataset.ruc).toLowerCase();
                    card.style.display = text.includes(term) ? '' : 'none';
                });
            });

            // ══════════ PAYMENT MODE ══════════
            function setPaymentMode(mode) {
                document.getElementById('modalidad_pago').value = mode;

                // Update prominent modalidad buttons
                const btnContado = document.getElementById('btn-modalidad-contado');
                const btnCuotas  = document.getElementById('btn-modalidad-cuotas');
                const checkContado = document.getElementById('check-contado');
                const checkCuotas  = document.getElementById('check-cuotas');

                if (mode === 'CONTADO') {
                    btnContado.classList.add('border-primary', 'bg-indigo-50/60', 'dark:bg-indigo-900/20', 'ring-2', 'ring-primary');
                    btnContado.classList.remove('border-slate-200', 'dark:border-slate-700', 'bg-white', 'dark:bg-slate-800');
                    btnCuotas.classList.remove('border-primary', 'bg-indigo-50/60', 'dark:bg-indigo-900/20', 'ring-2', 'ring-primary');
                    btnCuotas.classList.add('border-slate-200', 'dark:border-slate-700', 'bg-white', 'dark:bg-slate-800');
                    if (checkContado) checkContado.classList.remove('hidden');
                    if (checkCuotas)  checkCuotas.classList.add('hidden');
                } else {
                    btnCuotas.classList.add('border-primary', 'bg-indigo-50/60', 'dark:bg-indigo-900/20', 'ring-2', 'ring-primary');
                    btnCuotas.classList.remove('border-slate-200', 'dark:border-slate-700', 'bg-white', 'dark:bg-slate-800');
                    btnContado.classList.remove('border-primary', 'bg-indigo-50/60', 'dark:bg-indigo-900/20', 'ring-2', 'ring-primary');
                    btnContado.classList.add('border-slate-200', 'dark:border-slate-700', 'bg-white', 'dark:bg-slate-800');
                    if (checkCuotas)  checkCuotas.classList.remove('hidden');
                    if (checkContado) checkContado.classList.add('hidden');
                }

                document.getElementById('texto_pagos').textContent = mode === 'CONTADO' ? 'Registrá los pagos recibidos al contado.' : 'Registrá las entregas iniciales (si aplica).';
                document.getElementById('label_total_pagos').textContent = mode === 'CONTADO' ? 'Total pagos registrados' : 'Total entregas iniciales';
                document.getElementById('label_saldo').textContent = mode === 'CONTADO' ? 'Saldo pendiente' : 'A Financiar';

                document.getElementById('seccion_plan_cuotas').style.display = mode === 'CONTADO' ? 'none' : 'block';

                // Auto-fill price from vehicle if vehicle is already selected
                _autoFillPrecioFromModalidad();

                calcularTotalPagos(); // Recalculate colors based on mode
            }

            // ══════════ COMMON PAYMENTS (PAGOS / ENTREGAS) ══════════
            function agregarPago() {
                const idx = pagoCount++;
                const html = `
                                    <div class="payment-entry" id="pago_row_${idx}">
                                        <div class="form-grid" style="grid-template-columns:1fr 1fr 1fr auto;gap:.75rem;align-items:end">
                                            <div class="form-group">
                                                <label>Tipo de pago</label>
                                                <select name="pagos[${idx}][tipo]" onchange="toggleCamposPago(${idx}, this.value)">
                                                    <option value="EFECTIVO">💵 Efectivo</option>
                                                    <option value="TRANSFERENCIA">🏦 Transferencia</option>
                                                    <option value="VEHICULO_CANJE">🚗 Vehículo parte de pago</option>
                                                    <option value="CHEQUE">📝 Cheque</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Monto USD</label>
                                                <input type="number" name="pagos[${idx}][monto_usd]" step="0.01" min="0" class="pago-monto" oninput="calcularTotalPagos()">
                                            </div>
                                            <div class="form-group" id="ref_container_${idx}" style="display:none">
                                                <label>Referencia / Banco</label>
                                                <input type="text" name="pagos[${idx}][referencia]" placeholder="Nro. transferencia...">
                                            </div>
                                            <div class="form-group" id="canje_container_${idx}" style="display:none">
                                                <label>Vehículo en Canje</label>
                                                <select name="pagos[${idx}][vehiculo_canje_id]">
                                                    <option value="">— Seleccionar —</option>
                                                    @foreach($vehiculos_canje ?? [] as $vc)
                                                        <option value="{{ $vc->id }}">{{ $vc->marca }} {{ $vc->modelo }} — {{ $vc->numero_chasis }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <button type="button" class="btn btn-ghost" onclick="eliminarPago(${idx})" style="color:var(--danger);padding:.4rem .6rem" title="Eliminar">🗑️</button>
                                        </div>
                                    </div>`;
                document.getElementById('pagos_container').insertAdjacentHTML('beforeend', html);
            }

            function eliminarPago(idx) {
                const row = document.getElementById('pago_row_' + idx);
                if (row) row.remove();
                calcularTotalPagos();
            }

            function toggleCamposPago(idx, tipo) {
                const ref = document.getElementById('ref_container_' + idx);
                const canje = document.getElementById('canje_container_' + idx);
                if (ref) ref.style.display = (tipo === 'TRANSFERENCIA' || tipo === 'CHEQUE') ? 'flex' : 'none';
                if (canje) canje.style.display = tipo === 'VEHICULO_CANJE' ? 'flex' : 'none';
            }

            function calcularTotalPagos() {
                let total = 0;
                document.querySelectorAll('.pago-monto').forEach(input => {
                    total += parseFloat(input.value) || 0;
                });
                document.getElementById('total_pagos_display').textContent = '$ ' + formatNumber(total);

                const precioBase = parseFloat(document.getElementById('precio_usd').value) || 0;
                const descuento = parseFloat(document.getElementById('descuento_usd').value) || 0;
                const precioFinal = Math.max(0, precioBase - descuento);

                const saldo = Math.max(0, precioFinal - total);

                document.getElementById('precio_final_display').textContent = '$ ' + formatNumber(precioFinal);
                document.getElementById('plan_precio_base').textContent = '$ ' + formatNumber(precioBase);
                document.getElementById('plan_descuento').textContent = '$ ' + formatNumber(descuento);
                document.getElementById('plan_precio_final').textContent = '$ ' + formatNumber(precioFinal);

                const saldoEl = document.getElementById('saldo_pendiente_display');
                saldoEl.textContent = '$ ' + formatNumber(saldo);

                const isContado = document.getElementById('modalidad_pago').value === 'CONTADO';
                if (isContado) {
                    saldoEl.style.color = saldo <= 0 ? 'var(--success)' : 'var(--danger)';
                } else {
                    saldoEl.style.color = 'var(--accent)'; // In cuotas, balance is what we finance
                    document.getElementById('capital_usd_input').value = saldo.toFixed(2);
                    document.getElementById('capital_total_usd_visual').value = saldo.toFixed(2);
                    recalcularCuotasManuales();
                }
            }

            // ══════════ CUOTAS SECTION ══════════
            function onTipoPlanChange() {
                const tipo = document.getElementById('tipo_plan').value;
                document.getElementById('auto-config').style.display = tipo === 'MANUAL' ? 'none' : 'block';
                document.getElementById('manual-config').style.display = tipo === 'MANUAL' ? 'block' : 'none';
            }

            function addCuotaManual(tipo) {
                cuotaIdx++;
                const container = document.getElementById('cuotas-container');
                const existingDates = container.querySelectorAll('input[name$="[fecha]"]');
                let nextDate = new Date();
                nextDate.setMonth(nextDate.getMonth() + 1);
                if (existingDates.length > 0) {
                    const lastDate = new Date(existingDates[existingDates.length - 1].value);
                    lastDate.setMonth(lastDate.getMonth() + 1);
                    nextDate = lastDate;
                }
                const bgColor = tipo === 'REFUERZO' ? 'background:rgba(245,158,11,.08);border-radius:6px;padding:4px;' : '';

                const html = `
                                    <div class="cuota-row" id="cuota_${cuotaIdx}">
                                        <div style="${bgColor}"><strong style="font-size:.75rem;${tipo === 'REFUERZO' ? 'color:var(--warning)' : ''}">${cuotaIdx}</strong></div>
                                        <input type="date" name="cuotas_manual[${cuotaIdx}][fecha]" value="${nextDate.toISOString().split('T')[0]}">
                                        <select name="cuotas_manual[${cuotaIdx}][tipo]">
                                            <option value="REGULAR" ${tipo === 'REGULAR' ? 'selected' : ''}>Regular</option>
                                            <option value="REFUERZO" ${tipo === 'REFUERZO' ? 'selected' : ''}>Refuerzo</option>
                                        </select>
                                        <input type="number" name="cuotas_manual[${cuotaIdx}][monto]" step="0.01" min="0" value="0" class="cuota-monto" oninput="recalcularCuotasManuales()" style="${tipo === 'REFUERZO' ? 'border-color:var(--warning)' : ''}">
                                        <button type="button" class="btn btn-ghost" onclick="removeCuota(${cuotaIdx})" style="color:var(--danger);padding:.2rem">✕</button>
                                    </div>`;
                container.insertAdjacentHTML('beforeend', html);
                recalcularCuotasManuales();
            }

            function removeCuota(idx) {
                document.getElementById('cuota_' + idx)?.remove();
                recalcularCuotasManuales();
            }

            function recalcularCuotasManuales() {
                if (document.getElementById('modalidad_pago').value !== 'CUOTAS') return;
                let totalCuotas = 0;
                document.querySelectorAll('.cuota-monto').forEach(el => totalCuotas += parseFloat(el.value) || 0);
                document.getElementById('total_cuotas_sum').textContent = '$ ' + formatNumber(totalCuotas);

                const saldo = parseFloat(document.getElementById('capital_usd_input').value) || 0;
                const diff = totalCuotas - saldo;
                const diffEl = document.getElementById('diferencia_cuotas');
                diffEl.textContent = (diff >= 0 ? '+' : '') + '$ ' + formatNumber(diff);
                diffEl.style.color = Math.abs(diff) < 0.01 ? 'var(--success)' : 'var(--danger)';
            }


            // ══════════ PRICING ══════════
            function calcRent() {
                const costo = parseFloat(document.getElementById('valor_libro_span')?.dataset?.costoRaw) || 0;
                const precio = parseFloat(document.getElementById('precio_usd').value) || 0;
                const descuento = parseFloat(document.getElementById('descuento_usd').value) || 0;

                const precioFinal = Math.max(0, precio - descuento);
                const rent = precioFinal - costo;
                const span = document.getElementById('rent_span');
                span.textContent = '$ ' + formatNumber(rent);
                span.style.color = rent >= 0 ? 'var(--success)' : 'var(--danger)';
                calcularTotalPagos();
            }

            async function fetchRates() {
                try {
                    const fecha = document.querySelector('input[name="fecha_venta"]').value || new Date().toISOString().split('T')[0];
                    const res = await fetch(`{{ route('api.cotizaciones.today') }}?fecha=${fecha}`);
                    currentRates = await res.json();
                    calcularTotalVentaUsd();
                } catch (e) { console.error('Error fetching rates', e); }
            }

            function calcularTotalVentaUsd() {
                const precioMoneda = parseFloat(document.querySelector('input[name="precio_venta_moneda"]').value) || 0;
                const dctoMoneda = parseFloat(document.querySelector('input[name="descuento_moneda"]').value) || 0;
                const moneda = document.querySelector('select[name="moneda_venta"]').value;

                let precioUsd = precioMoneda;
                let dctoUsd = dctoMoneda;
                let tasa = 1;

                if (moneda === 'PYG') {
                    tasa = parseFloat(currentRates.PYG) || 1;
                    precioUsd = precioMoneda / tasa;
                    dctoUsd = dctoMoneda / tasa;
                }
                else if (moneda === 'BRL') {
                    tasa = parseFloat(currentRates.BRL) || 1;
                    precioUsd = precioMoneda / tasa;
                    dctoUsd = dctoMoneda / tasa;
                }

                document.getElementById('precio_usd').value = precioUsd.toFixed(2);
                document.getElementById('descuento_usd').value = dctoUsd.toFixed(2);
                document.querySelector('input[name="tasa_cambio_venta"]').value = tasa.toFixed(2);
                calcRent();
            }

            // ══════════ RESUMEN ══════════
            function buildResumen() {
                const mode = document.getElementById('modalidad_pago').value || 'CONTADO';
                
                // Items Summary
                const itemsList = document.getElementById('resumen_items_list');
                itemsList.innerHTML = '';
                
                itemsVenta.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'flex flex-col border-b border-slate-100 dark:border-slate-700/50 pb-2 last:border-0';
                    div.innerHTML = `
                        <div class="flex justify-between items-start">
                            <span class="font-bold text-sm text-slate-700 dark:text-slate-200 line-clamp-1">${item.descripcion}</span>
                            <span class="text-xs font-mono text-slate-500">$ ${formatNumber(item.precio_unitario_usd * item.cantidad)}</span>
                        </div>
                        <div class="flex justify-between items-center mt-0.5">
                            <span class="text-[0.6rem] text-slate-400 uppercase tracking-widest">${item.type === 'camion' ? 'Camión' : 'Repuesto'}</span>
                            <span class="text-[0.65rem] text-slate-500">${item.cantidad} x $ ${formatNumber(item.precio_unitario_usd)}</span>
                        </div>
                    `;
                    itemsList.appendChild(div);
                });

                if (selectedCliente) {
                    document.getElementById('resumen_cliente').textContent = selectedCliente.nombre;
                    document.getElementById('resumen_cliente_ruc').textContent = 'RUC: ' + (selectedCliente.ruc || 'N/A') + ' · ' + selectedCliente.pais;
                }
                const precioBase = parseFloat(document.getElementById('precio_usd').value) || 0;
                const descuento = parseFloat(document.getElementById('descuento_usd').value) || 0;
                const precioFinal = Math.max(0, precioBase - descuento);

                document.getElementById('resumen_precio').innerHTML = '$ ' + formatNumber(precioFinal) + ' USD' +
                    (descuento > 0 ? `<br><span style="font-size:.7rem;color:var(--danger);font-weight:normal">- $ ${formatNumber(descuento)} desc.</span>` : '');

                document.getElementById('resumen_modalidad').textContent = mode === 'CONTADO' ? 'Contado' : ('Cuotas (' + document.getElementById('tipo_plan').value + ')');

                const costo = parseFloat(document.getElementById('valor_libro_span')?.dataset?.costoRaw) || 0;
                const rent = precioFinal - costo;
                const rentEl = document.getElementById('resumen_rentabilidad');
                rentEl.textContent = '$ ' + formatNumber(rent);
                rentEl.className = rent >= 0 ? 'text-xl font-bold text-emerald-600 dark:text-emerald-400' : 'text-xl font-bold text-red-600 dark:text-red-400';

                // Build payment summary
                let html = '';
                let totalPagos = 0;
                document.querySelectorAll('.payment-entry').forEach(row => {
                    const tipo = row.querySelector('select')?.value || 'EFECTIVO';
                    const monto = parseFloat(row.querySelector('.pago-monto')?.value) || 0;
                    if (monto > 0) {
                        totalPagos += monto;
                        const labels = { EFECTIVO: 'Efectivo', TRANSFERENCIA: 'Transferencia', VEHICULO_CANJE: 'Vehículo canje', CHEQUE: 'Cheque' };
                        html += `<div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-700/50 text-sm">
                                                            <span class="text-slate-600 dark:text-slate-300">${mode === 'CONTADO' ? 'Pago' : 'Entrega'}: ${labels[tipo] || tipo}</span>
                                                            <strong class="text-slate-800 dark:text-slate-100">$ ${formatNumber(monto)}</strong>
                                                         </div>`;
                    }
                });

                if (mode === 'CUOTAS') {
                    const financiar = Math.max(0, precioFinal - totalPagos);
                    html += `<div class="flex justify-between py-2 text-sm">
                                                        <span class="text-slate-600 dark:text-slate-300">A financiar en cuotas</span>
                                                        <strong class="text-indigo-600 dark:text-indigo-400">$ ${formatNumber(financiar)}</strong>
                                                     </div>`;
                }
                document.getElementById('resumen_pagos_box').innerHTML = html ? `
                                            <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-5">
                                                <div class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
                                                    Detalle de ${mode === 'CONTADO' ? 'pagos' : 'entregas y cuotas'}
                                                </div>
                                                ${html}
                                            </div>` : '';
            }

            // ══════════ MODAL CLIENTE ══════════
            function abrirModalCliente() { document.getElementById('modalCliente').style.display = 'flex'; }
            function cerrarModalCliente() { document.getElementById('modalCliente').style.display = 'none'; document.getElementById('formModalCliente').reset(); }

            document.getElementById('formModalCliente').addEventListener('submit', async (e) => {
                e.preventDefault();
                const form = e.target;
                const btn = form.querySelector('button[type="submit"]');
                btn.disabled = true; btn.textContent = 'Guardando...';
                try {
                    const res = await fetch("{{ route('clientes.store') }}", {
                        method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: new FormData(form)
                    });
                    const data = await res.json();
                    if (data.success) {
                        // Add card to grid
                        const grid = document.getElementById('clientesGrid');
                        const card = document.createElement('div');
                        card.className = 'client-card group p-4 py-5 cursor-pointer bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl transition-all hover:border-primary hover:shadow-md [&.selected]:ring-2 [&.selected]:ring-primary [&.selected]:border-primary [&.selected]:bg-indigo-50/50 dark:[&.selected]:bg-indigo-900/10 shadow-sm';
                        card.dataset.id = data.cliente.id;
                        card.dataset.nombre = data.cliente.razon_social;
                        card.dataset.ruc = '';
                        card.dataset.telefono = '';
                        card.dataset.pais = '';
                        card.onclick = function () { seleccionarCliente(this); };
                        card.innerHTML = `<div class="flex items-start gap-3 mb-2">
                                                            <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-slate-500 dark:text-slate-400 flex-shrink-0 border border-slate-200 dark:border-slate-700 group-[.selected]:bg-primary/10 group-[.selected]:text-primary group-[.selected]:border-primary/30 transition-colors">
                                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="font-bold text-[1.05rem] text-slate-800 dark:text-slate-100 group-[.selected]:text-primary transition-colors truncate">${data.cliente.razon_social}</div>
                                                                <div class="text-[0.75rem] text-slate-500 dark:text-slate-400 mt-0.5">Recién creado</div>
                                                            </div>
                                                        </div>`;
                        grid.prepend(card);
                        seleccionarCliente(card);
                        cerrarModalCliente();
                    } else { alert('Error al guardar.'); }
                } catch (err) { console.error(err); alert('Error de conexión.'); }
                finally { btn.disabled = false; btn.textContent = '💾 Guardar Cliente'; }
            });

            // ══════════ ITEM MANAGEMENT ══════════
            let itemsVenta = [];

            function switchItemTab(tab) {
                const btnCamiones = document.getElementById('tab-camiones');
                const btnRepuestos = document.getElementById('tab-repuestos');
                const gridCamiones = document.getElementById('item-grid-camiones');
                const gridRepuestos = document.getElementById('item-grid-repuestos');
                const searchCamiones = document.getElementById('search-camiones-container');
                const searchRepuestos = document.getElementById('search-repuestos-container');

                if (tab === 'camiones') {
                    btnCamiones.classList.add('bg-white', 'dark:bg-slate-800', 'shadow-sm', 'text-primary');
                    btnCamiones.classList.remove('text-slate-500');
                    btnRepuestos.classList.remove('bg-white', 'dark:bg-slate-800', 'shadow-sm', 'text-primary');
                    btnRepuestos.classList.add('text-slate-500');
                    
                    gridCamiones.classList.remove('hidden');
                    gridRepuestos.classList.add('hidden');
                    searchCamiones.classList.remove('hidden');
                    searchRepuestos.classList.add('hidden');
                } else {
                    btnRepuestos.classList.add('bg-white', 'dark:bg-slate-800', 'shadow-sm', 'text-primary');
                    btnRepuestos.classList.remove('text-slate-500');
                    btnCamiones.classList.remove('bg-white', 'dark:bg-slate-800', 'shadow-sm', 'text-primary');
                    btnCamiones.classList.add('text-slate-500');
                    
                    gridRepuestos.classList.remove('hidden');
                    gridCamiones.classList.add('hidden');
                    searchRepuestos.classList.remove('hidden');
                    searchCamiones.classList.add('hidden');
                }
            }

            function addItemToVenta(type, el) {
                const id = el.dataset.id;
                
                if (type === 'camion') {
                    // Only one vehicle allowed per sale in this simplified model for now
                    // Remove previous vehicle if exists
                    itemsVenta = itemsVenta.filter(i => i.type !== 'camion');
                    
                    itemsVenta.push({
                        type: 'camion',
                        id: id,
                        descripcion: `${el.dataset.marca} ${el.dataset.modelo} (${el.dataset.chasis})`,
                        cantidad: 1,
                        precio_unitario_usd: parseFloat(el.dataset.precioContado) || parseFloat(el.dataset.precioSugerido) || 0,
                        costo_usd: parseFloat(el.dataset.costo) || 0,
                    });
                    
                    // Highlight visually
                    document.querySelectorAll('.vehicle-card').forEach(c => c.classList.remove('selected'));
                    el.classList.add('selected');
                    document.getElementById('vehiculo_id_input').value = id;
                    
                    // Update vehicle snapshot for rent calculation (include pricing for modalidad switch)
                    selectedVehiculo = {
                        id: id, marca: el.dataset.marca, modelo: el.dataset.modelo,
                        chasis: el.dataset.chasis, costo: parseFloat(el.dataset.costo),
                        precioContado: parseFloat(el.dataset.precioContado) || 0,
                        precioCuotas: parseFloat(el.dataset.precioCuotas) || 0,
                        precioSugerido: parseFloat(el.dataset.precioSugerido) || 0,
                    };

                    // Update price labels on modalidad buttons
                    const labelContado = document.getElementById('label-precio-contado');
                    const labelCuotas  = document.getElementById('label-precio-cuotas');
                    if (labelContado) labelContado.textContent = selectedVehiculo.precioContado > 0
                        ? 'US$ ' + formatNumber(selectedVehiculo.precioContado) : 'Pago total en una sola operación';
                    if (labelCuotas)  labelCuotas.textContent  = selectedVehiculo.precioCuotas  > 0
                        ? 'US$ ' + formatNumber(selectedVehiculo.precioCuotas)  : 'Financiamiento en cuotas mensuales';
                } else {
                    // Check if already in cart
                    const existing = itemsVenta.find(i => i.type === 'repuesto' && i.id === id);
                    if (existing) {
                        existing.cantidad += 1;
                    } else {
                        itemsVenta.push({
                            type: 'repuesto',
                            id: id,
                            descripcion: el.dataset.descripcion,
                            cantidad: 1,
                            precio_unitario_usd: parseFloat(el.dataset.precioUsd) || 0,
                            costo_usd: parseFloat(el.dataset.costoUsd) || 0,
                        });
                    }
                }
                
                renderCart();
            }

            function updateItemQty(index, qty) {
                if (qty <= 0) {
                    removeItemFromCart(index);
                    return;
                }
                itemsVenta[index].cantidad = parseFloat(qty);
                renderCart();
            }

            function removeItemFromCart(index) {
                const item = itemsVenta[index];
                if (item.type === 'camion') {
                    document.getElementById('vehiculo_id_input').value = '';
                    document.querySelectorAll('.vehicle-card.selected').forEach(c => c.classList.remove('selected'));
                    selectedVehiculo = null;
                }
                itemsVenta.splice(index, 1);
                renderCart();
            }

            function renderCart() {
                const body = document.getElementById('cart-body');
                const emptyRow = document.getElementById('cart-empty-row');
                
                // Clear rows except empty row
                Array.from(body.rows).forEach(row => {
                    if (row.id !== 'cart-empty-row') row.remove();
                });
                
                if (itemsVenta.length === 0) {
                    emptyRow.classList.remove('hidden');
                    document.getElementById('btnStep1Next').disabled = true;
                    document.getElementById('cart-total-usd').textContent = 'US$ 0,00';
                    document.getElementById('cart-count').textContent = '0 items añadidos';
                    return;
                }
                
                emptyRow.classList.add('hidden');
                document.getElementById('btnStep1Next').disabled = false;
                
                let total = 0;
                let totalCosto = 0;
                
                itemsVenta.forEach((item, index) => {
                    const subtotal = item.precio_unitario_usd * item.cantidad;
                    total += subtotal;
                    totalCosto += (item.costo_usd * item.cantidad);
                    
                    const row = document.createElement('tr');
                    row.className = 'bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors';
                    row.innerHTML = `
                        <td class="px-4 py-3 rounded-l-xl border-y border-l border-slate-100 dark:border-slate-700">
                            <div class="font-bold text-slate-700 dark:text-slate-200">${item.descripcion}</div>
                            <div class="text-[0.6rem] text-slate-400 uppercase tracking-widest">${item.type === 'camion' ? 'CAMIÓN' : 'REPUESTO'}</div>
                            <input type="hidden" name="items[${index}][itemable_id]" value="${item.id}">
                            <input type="hidden" name="items[${index}][itemable_type]" value="${item.type === 'camion' ? 'App\\Models\\Vehicle' : 'App\\Models\\StockRepuesto'}">
                            <input type="hidden" name="items[${index}][descripcion]" value="${item.descripcion}">
                            <input type="hidden" name="items[${index}][precio_unitario_usd]" value="${item.precio_unitario_usd}">
                            <input type="hidden" name="items[${index}][costo_snapshot_usd]" value="${item.costo_usd}">
                        </td>
                        <td class="px-4 py-3 border-y border-slate-100 dark:border-slate-700 text-center">
                            <input type="number" name="items[${index}][cantidad]" value="${item.cantidad}" step="0.01" min="0.01" 
                                class="form-input w-20 text-center text-sm p-1" onchange="updateItemQty(${index}, this.value)">
                        </td>
                        <td class="px-4 py-3 border-y border-slate-100 dark:border-slate-700 text-right font-mono text-xs">
                            US$ ${formatNumber(item.precio_unitario_usd)}
                        </td>
                        <td class="px-4 py-3 border-y border-slate-100 dark:border-slate-700 text-right font-bold text-primary">
                            US$ ${formatNumber(subtotal)}
                        </td>
                        <td class="px-4 py-3 rounded-r-xl border-y border-r border-slate-100 dark:border-slate-700 text-center">
                            <button type="button" onclick="removeItemFromCart(${index})" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </td>
                    `;
                    body.appendChild(row);
                });
                
                document.getElementById('cart-total-usd').textContent = 'US$ ' + formatNumber(total);
                document.getElementById('cart-count').textContent = `${itemsVenta.length} items añadidos`;
                
                // Update Step 3 inputs automatically
                const precioInput = document.getElementById('precio_usd');
                if (precioInput) {
                    precioInput.value = total.toFixed(2);
                    // Also update price in original currency if needed
                    const selectMoneda = document.querySelector('select[name="moneda_venta"]');
                    const precioMonedaInput = document.querySelector('input[name="precio_venta_moneda"]');
                    if (selectMoneda && selectMoneda.value === 'USD') {
                        precioMonedaInput.value = total.toFixed(2);
                    } else {
                        calcularPrecioMonedaDesdeUsd();
                    }
                }
                
                // Update vehicle snapshot for rent calculations
                const spanCosto = document.getElementById('valor_libro_span');
                if (spanCosto) {
                    spanCosto.dataset.costoRaw = totalCosto;
                    spanCosto.textContent = '$ ' + formatNumber(totalCosto);
                }
                
                calcRent();
            }

            function calcularPrecioMonedaDesdeUsd() {
                const precioUsd = parseFloat(document.getElementById('precio_usd').value) || 0;
                const moneda = document.querySelector('select[name="moneda_venta"]').value;
                const precioMonedaInput = document.querySelector('input[name="precio_venta_moneda"]');
                
                let tasa = 1;
                if (moneda === 'PYG') tasa = parseFloat(currentRates.PYG) || 1;
                else if (moneda === 'BRL') tasa = parseFloat(currentRates.BRL) || 1;
                
                precioMonedaInput.value = (precioUsd * tasa).toFixed(2);
                document.querySelector('input[name="tasa_cambio_venta"]').value = tasa.toFixed(2);
            }

            // ══════════ MODAL CONFIRMACIÓN (DOBLE FACTOR) ══════════
            function abrirModalConfirmacion() {
                const modal = document.getElementById('modalConfirmacion');
                const body = document.getElementById('confirmacion-body');
                const warningsBox = document.getElementById('confirmacion-warnings');
                const warningsList = document.getElementById('confirmacion-warnings-list');
                const errorsBox = document.getElementById('confirmacion-errors');
                const errorsList = document.getElementById('confirmacion-errors-list');
                const btnFinal = document.getElementById('btnConfirmarFinal');

                body.innerHTML = '';
                warningsList.innerHTML = '';
                errorsList.innerHTML = '';
                warningsBox.classList.add('hidden');
                errorsBox.classList.add('hidden');

                const errors = [];
                const warnings = [];
                const checks = [];

                // ── Validation checks ──
                // 1. Items
                if (itemsVenta.length > 0) {
                    const desc = itemsVenta.map(i => i.descripcion).join(', ');
                    checks.push({ ok: true, label: 'Items seleccionados', detail: `${itemsVenta.length} item(s): ${desc}` });
                } else {
                    errors.push('No se seleccionó ningún ítem para la venta.');
                    checks.push({ ok: false, label: 'Items seleccionados', detail: 'Ninguno' });
                }

                // 2. Cliente
                if (selectedCliente) {
                    checks.push({ ok: true, label: 'Cliente', detail: `${selectedCliente.nombre} (RUC: ${selectedCliente.ruc || 'N/A'})` });
                } else {
                    errors.push('No se seleccionó un cliente.');
                    checks.push({ ok: false, label: 'Cliente', detail: 'No seleccionado' });
                }

                // 3. Precio
                const precioUsd = parseFloat(document.getElementById('precio_usd').value) || 0;
                const descuentoUsd = parseFloat(document.getElementById('descuento_usd').value) || 0;
                const precioFinal = Math.max(0, precioUsd - descuentoUsd);
                if (precioFinal > 0) {
                    checks.push({ ok: true, label: 'Precio de venta', detail: `US$ ${formatNumber(precioFinal)}${descuentoUsd > 0 ? ' (desc: US$ ' + formatNumber(descuentoUsd) + ')' : ''}` });
                } else {
                    errors.push('El precio de venta es $0 o no fue ingresado.');
                    checks.push({ ok: false, label: 'Precio de venta', detail: 'US$ 0.00' });
                }

                // 4. Modalidad
                const modalidad = document.getElementById('modalidad_pago').value;
                checks.push({ ok: true, label: 'Modalidad', detail: modalidad === 'CONTADO' ? 'Contado' : 'Plan de Cuotas' });

                // 5. Pagos
                let totalPagos = 0;
                document.querySelectorAll('.pago-monto').forEach(input => {
                    totalPagos += parseFloat(input.value) || 0;
                });

                if (modalidad === 'CONTADO') {
                    const saldo = precioFinal - totalPagos;
                    if (totalPagos >= precioFinal && precioFinal > 0) {
                        checks.push({ ok: true, label: 'Pagos registrados', detail: `US$ ${formatNumber(totalPagos)} — Cubierto` });
                    } else if (totalPagos > 0) {
                        warnings.push(`Venta al contado con saldo pendiente de US$ ${formatNumber(saldo)}. ¿Seguro que desea continuar?`);
                        checks.push({ ok: false, label: 'Pagos registrados', detail: `US$ ${formatNumber(totalPagos)} — Faltan US$ ${formatNumber(saldo)}` });
                    } else {
                        warnings.push('No se registró ningún pago para esta venta al contado.');
                        checks.push({ ok: false, label: 'Pagos registrados', detail: 'Ninguno' });
                    }
                } else {
                    // Cuotas
                    if (totalPagos > 0) {
                        checks.push({ ok: true, label: 'Entregas iniciales', detail: `US$ ${formatNumber(totalPagos)}` });
                    } else {
                        warnings.push('No se registraron entregas iniciales. El total se financiará en cuotas.');
                        checks.push({ ok: false, label: 'Entregas iniciales', detail: 'Ninguna' });
                    }

                    // Validate cuotas plan
                    const tipoPlan = document.getElementById('tipo_plan').value;
                    if (tipoPlan === 'MANUAL') {
                        let totalCuotas = 0;
                        document.querySelectorAll('.cuota-monto').forEach(el => totalCuotas += parseFloat(el.value) || 0);
                        const capitalFinanciar = precioFinal - totalPagos;
                        if (totalCuotas > 0) {
                            const diff = Math.abs(totalCuotas - capitalFinanciar);
                            if (diff < 0.01) {
                                checks.push({ ok: true, label: 'Plan de cuotas', detail: `${document.querySelectorAll('.cuota-row').length} cuotas — US$ ${formatNumber(totalCuotas)}` });
                            } else {
                                warnings.push(`El total de cuotas (US$ ${formatNumber(totalCuotas)}) no coincide con el capital a financiar (US$ ${formatNumber(capitalFinanciar)}). Diferencia: US$ ${formatNumber(totalCuotas - capitalFinanciar)}`);
                                checks.push({ ok: false, label: 'Plan de cuotas', detail: `${document.querySelectorAll('.cuota-row').length} cuotas — US$ ${formatNumber(totalCuotas)} (diferencia)` });
                            }
                        } else {
                            warnings.push('No se configuraron cuotas manuales.');
                            checks.push({ ok: false, label: 'Plan de cuotas', detail: 'Sin cuotas configuradas' });
                        }
                    } else {
                        const numCuotas = parseInt(document.getElementById('numero_cuotas')?.value) || 0;
                        if (numCuotas > 0) {
                            checks.push({ ok: true, label: 'Plan de cuotas', detail: `${tipoPlan} — ${numCuotas} cuotas` });
                        } else {
                            errors.push('Número de cuotas no definido.');
                            checks.push({ ok: false, label: 'Plan de cuotas', detail: 'Sin configurar' });
                        }
                    }
                }

                // 6. Fecha
                const fecha = document.querySelector('input[name="fecha_venta"]').value;
                if (fecha) {
                    checks.push({ ok: true, label: 'Fecha de venta', detail: fecha });
                } else {
                    errors.push('No se ingresó la fecha de venta.');
                    checks.push({ ok: false, label: 'Fecha de venta', detail: 'No definida' });
                }

                // 7. Rentabilidad check
                const costoTotal = parseFloat(document.getElementById('valor_libro_span')?.dataset?.costoRaw) || 0;
                const rentabilidad = precioFinal - costoTotal;
                if (rentabilidad < 0) {
                    warnings.push(`La venta genera una pérdida de US$ ${formatNumber(Math.abs(rentabilidad))}.`);
                }

                // ── Render checklist ──
                checks.forEach(c => {
                    const icon = c.ok
                        ? '<svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>'
                        : '<svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>';
                    body.innerHTML += `
                        <div class="flex items-start gap-3 p-3 rounded-xl ${c.ok ? 'bg-emerald-50/50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800/30' : 'bg-amber-50/50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-800/30'}">
                            ${icon}
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold uppercase tracking-wider ${c.ok ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400'}">${c.label}</div>
                                <div class="text-sm text-slate-700 dark:text-slate-300 mt-0.5 truncate" title="${c.detail}">${c.detail}</div>
                            </div>
                        </div>`;
                });

                // Show warnings
                if (warnings.length > 0) {
                    warningsBox.classList.remove('hidden');
                    warnings.forEach(w => {
                        warningsList.innerHTML += `<li>${w}</li>`;
                    });
                }

                // Show errors
                if (errors.length > 0) {
                    errorsBox.classList.remove('hidden');
                    errors.forEach(e => {
                        errorsList.innerHTML += `<li>${e}</li>`;
                    });
                }

                // Enable/disable final button
                btnFinal.disabled = errors.length > 0;

                modal.style.display = 'flex';
                modal.classList.remove('hidden');
            }

            function cerrarModalConfirmacion() {
                const modal = document.getElementById('modalConfirmacion');
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }

            function confirmarYRegistrar() {
                cerrarModalConfirmacion();
                document.getElementById('ventaForm').submit();
            }

            // Close modal on backdrop click
            document.getElementById('modalConfirmacion').addEventListener('click', function(e) {
                if (e.target === this) cerrarModalConfirmacion();
            });

            // Init
            fetchRates();
            document.querySelector('input[name="fecha_venta"]').addEventListener('change', fetchRates);
            onTipoPlanChange();
        </script>
    @endpush
@endsection