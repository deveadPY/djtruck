@extends('layouts.app')
@section('title', 'Nuevo Repuesto')
@section('page-title', 'Registrar Repuesto')

@section('content')
    @if($errors->any())
        <div class="flash-error">{{ $errors->first() }}
    </div>@endif

@section('content')
    <div class="mb-6">
        <a href="{{ route('repuestos.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Volver al Inventario
        </a>
    </div>

    <div class="erp-card max-w-4xl mx-auto">
        <div class="erp-card-header">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25-3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <h2 class="text-lg font-bold">Registrar Nuevo Producto</h2>
            </div>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('repuestos.store') }}" data-confirm="¿Desea registrar este repuesto?" class="space-y-6">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Código del Repuesto <span class="text-red-500">*</span></label>
                        <input type="text" name="codigo" value="{{ old('codigo') }}" placeholder="Ej: FLT-MB-100" class="form-input" required maxlength="50">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Unidad de Medida <span class="text-red-500">*</span></label>
                        <select name="unidad_medida" class="form-input" required>
                            @foreach(['UND' => 'Unidad', 'LTS' => 'Litros', 'KG' => 'Kilogramos', 'MT' => 'Metros', 'JGO' => 'Juego', 'PAR' => 'Par'] as $v => $l)
                                <option value="{{ $v }}" @selected(old('unidad_medida') == $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group sm:col-span-2">
                        <label class="form-label">Descripción Detallada <span class="text-red-500">*</span></label>
                        <input type="text" name="descripcion" value="{{ old('descripcion') }}" placeholder="Filtro de aceite Mercedes-Benz Actros MP4" class="form-input" required maxlength="300">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Marca Compatible / Fabricante</label>
                        <input type="text" name="marca_compatible" value="{{ old('marca_compatible') }}" placeholder="Mercedes-Benz, Volvo, etc." class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Proveedor</label>
                        <select name="proveedor_id" class="form-input">
                            <option value="">-- Sin proveedor --</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id }}" @selected(old('proveedor_id') == $p->id)>{{ $p->razon_social }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Punto de Re-pedido (Min)</label>
                        <div class="relative font-bold text-amber-500">
                            <input type="number" name="stock_minimo" value="{{ old('stock_minimo', 0) }}" step="0.001" min="0" class="form-input border-amber-500/30 focus:border-amber-500">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[0.6rem] font-bold uppercase">Mín.</span>
                        </div>
                    </div>
                </div>

                <div class="bg-surface2/50 p-4 rounded-xl border border-border mt-4">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground mb-4">Información de Precios y Costos</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Moneda de Base</label>
                            <select name="moneda_origen" id="moneda_sel" class="form-input bg-surface1">
                                <option value="USD">USD - Dólar Estadounidense</option>
                                <option value="PYG">PYG - Guaraníes</option>
                                <option value="BRL">BRL - Real Brasileño</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Factor de Conversión (a USD)</label>
                            <input type="number" name="tasa_cambio" value="1" step="0.0001" readonly class="form-input bg-surface3 cursor-not-allowed opacity-60">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                        <div class="form-group">
                            <label class="form-label text-[0.65rem]">Costo Moneda Origen</label>
                            <input type="number" name="costo_promedio_moneda" value="{{ old('costo_promedio_moneda') }}" step="0.01" min="0" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label text-[0.65rem]">Precio Venta Origen</label>
                            <input type="number" name="precio_venta_moneda" value="{{ old('precio_venta_moneda') }}" step="0.01" min="0" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label text-[0.65rem]">Costo Final (USD) <span class="text-red-500 font-bold">*</span></label>
                            <input type="number" name="costo_promedio_usd" value="{{ old('costo_promedio_usd') }}" step="0.01" min="0" class="form-input border-primary/40 font-bold" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label text-[0.65rem]">Venta Final (USD)</label>
                            <input type="number" name="precio_venta_usd" value="{{ old('precio_venta_usd') }}" step="0.01" min="0" class="form-input border-accent/40 font-bold">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <a href="{{ route('repuestos.index') }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-8">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                        </svg>
                        Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            let currentRates = { PYG: 1, BRL: 1 };

            async function fetchRates() {
                try {
                    const fecha = new Date().toISOString().split('T')[0];
                    const res = await fetch(`{{ route('api.cotizaciones.today') }}?fecha=${fecha}`);
                    const data = await res.json();
                    currentRates = data;
                    calcularTotalesUsd();
                } catch (e) { console.error('Error fetching rates', e); }
            }

            function calcularTotalesUsd() {
                const costoMo = parseFloat(document.querySelector('input[name="costo_promedio_moneda"]').value) || 0;
                const precioMo = parseFloat(document.querySelector('input[name="precio_venta_moneda"]').value) || 0;
                const moneda = document.getElementById('moneda_sel').value;

                let costoUsd = costoMo, precioUsd = precioMo;
                let tasaUsada = null;

                if (moneda === 'PYG') {
                    const rate = parseFloat(currentRates.PYG) || 1;
                    costoUsd = costoMo / rate;
                    precioUsd = precioMo / rate;
                    tasaUsada = rate;
                } else if (moneda === 'BRL') {
                    const rate = parseFloat(currentRates.BRL) || 1;
                    costoUsd = costoMo / rate;
                    precioUsd = precioMo / rate;
                    tasaUsada = rate;
                }

                document.querySelector('input[name="costo_promedio_usd"]').value = costoUsd ? costoUsd.toFixed(2) : '';
                document.querySelector('input[name="precio_venta_usd"]').value = precioUsd ? precioUsd.toFixed(2) : '';

                if (tasaUsada) {
                    document.querySelector('input[name="tasa_cambio"]').value = tasaUsada.toFixed(2);
                } else {
                    document.querySelector('input[name="tasa_cambio"]').value = '1';
                }
            }

            document.getElementById('moneda_sel').addEventListener('change', calcularTotalesUsd);
            document.querySelector('input[name="costo_promedio_moneda"]').addEventListener('input', calcularTotalesUsd);
            document.querySelector('input[name="precio_venta_moneda"]').addEventListener('input', calcularTotalesUsd);

            // Fetch initial rates on load
            fetchRates();
        </script>
    @endpush
@endsection