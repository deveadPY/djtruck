@extends('layouts.app')
@section('title', 'Editar Repuesto')
@section('page-title', 'Editar Repuesto')

@section('content')
    @if($errors->any())
        <div class="flash-error">{{ $errors->first() }}</div>@endif

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
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                </div>
                <div class="flex flex-col">
                    <h2 class="text-lg font-bold">Editar Producto</h2>
                    <span class="text-[0.65rem] font-mono text-muted-foreground uppercase tracking-widest">{{ $repuesto->codigo }}</span>
                </div>
            </div>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('repuestos.update', $repuesto->id) }}" data-confirm="¿Actualizar datos del repuesto?" class="space-y-6">
                @csrf @method('PUT')
                <div class="form-grid">
                    <div class="form-group sm:col-span-2">
                        <label class="form-label">Descripción Detallada <span class="text-red-500">*</span></label>
                        <input type="text" name="descripcion" value="{{ old('descripcion', $repuesto->descripcion) }}" class="form-input" required maxlength="300">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Marca Compatible / Fabricante</label>
                        <input type="text" name="marca_compatible" value="{{ old('marca_compatible', $repuesto->marca_compatible) }}" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Proveedor</label>
                        <select name="proveedor_id" class="form-input">
                            <option value="">-- Sin proveedor --</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id }}" @selected(old('proveedor_id', $repuesto->proveedor_id) == $p->id)>{{ $p->razon_social }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Unidad de Medida <span class="text-red-500">*</span></label>
                        <select name="unidad_medida" class="form-input" required>
                            @foreach(['UND' => 'Unidad', 'LTS' => 'Litros', 'KG' => 'Kilogramos', 'MT' => 'Metros', 'JGO' => 'Juego', 'PAR' => 'Par'] as $v => $l)
                                <option value="{{ $v }}" @selected(old('unidad_medida', $repuesto->unidad_medida) == $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Punto de Re-pedido (Min)</label>
                        <div class="relative font-bold text-amber-500">
                            <input type="number" name="stock_minimo" value="{{ old('stock_minimo', $repuesto->stock_minimo) }}" step="0.001" min="0" class="form-input border-amber-500/30">
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
                            <input type="number" name="costo_promedio_moneda" value="" step="0.01" min="0" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label text-[0.65rem]">Precio Venta Origen</label>
                            <input type="number" name="precio_venta_moneda" value="" step="0.01" min="0" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label text-[0.65rem]">Costo Final (USD) <span class="text-red-500 font-bold">*</span></label>
                            <input type="number" name="costo_promedio_usd" value="{{ old('costo_promedio_usd', $repuesto->costo_promedio_usd) }}" step="0.01" min="0" class="form-input border-primary/40 font-bold" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label text-[0.65rem]">Venta Final (USD)</label>
                            <input type="number" name="precio_venta_usd" value="{{ old('precio_venta_usd', $repuesto->precio_venta_usd) }}" step="0.01" min="0" class="form-input border-accent/40 font-bold">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                    <a href="{{ route('repuestos.index') }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-8">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Actualizar Producto
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
                const res = await fetch(`{{ route('cotizaciones.tasas-hoy') }}?fecha=${fecha}`);
                const data = await res.json();
                currentRates = data;
                calcularTotalesUsd();
            } catch(e) { console.error('Error fetching rates', e); }
        }

        function calcularTotalesUsd() {
            const costoMo = parseFloat(document.querySelector('input[name="costo_promedio_moneda"]').value) || 0;
            const precioMo = parseFloat(document.querySelector('input[name="precio_venta_moneda"]').value) || 0;
            const moneda = document.getElementById('moneda_sel').value;
            
            let costoUsd = costoMo, precioUsd = precioMo;
            let tasaUsada = null;

            if(moneda === 'PYG') {
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

            if(costoMo > 0) document.querySelector('input[name="costo_promedio_usd"]').value = costoUsd.toFixed(2);
            if(precioMo > 0) document.querySelector('input[name="precio_venta_usd"]').value = precioUsd.toFixed(2);

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