@extends('layouts.app')
@section('title', 'Registrar Gasto')
@section('page-title', 'Registrar Gasto de Vehículo')

@section('content')
    @if($errors->any())
        <div class="flash-error">{{ $errors->first() }}</div>@endif

    <div style="margin-bottom:1rem"><a href="{{ route('vehicles.show', $vehiculo->id) }}" class="btn btn-ghost">← Volver al
            vehículo</a></div>

    <div class="vehiculo-badge">
        <svg class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
        <div>
            <div style="font-weight:700">{{ $vehiculo->marca }} {{ $vehiculo->modelo }} ({{ $vehiculo->anio }})</div>
            <div style="font-size:.78rem;color:var(--text-muted)">Chasis: {{ $vehiculo->numero_chasis }} | Valor libro
                actual: <strong style="color:var(--accent)">$
                    {{ number_format($vehiculo->costo_origen_usd + $vehiculo->total_gastos_usd, 2, ',', '.') }} USD</strong></div>
        </div>
    </div>

    <div class="erp-card">
        <div class="erp-card-header">
            <h2>Nuevo gasto</h2>
        </div>
        <div class="erp-card-body">
            <form method="POST" action="{{ route('gastos.store', $vehiculo->id) }}" enctype="multipart/form-data" data-confirm="Confirmar registro de gasto">
                @csrf
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Concepto *</label>
                        <input type="text" name="concepto" value="{{ old('concepto') }}"
                            placeholder="Ej: Reparación de frenos" required maxlength="255" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Categoría *</label>
                        <select name="categoria" required class="form-input">
                            @foreach([
                                    'REPARACION_MECANICA' => 'Reparación Mecánica',
                                    'CHAPERIA_PINTURA' => 'Chapería y Pintura',
                                    'ELECTRICIDAD' => 'Electricidad',
                                    'NEUMATICOS' => 'Neumáticos',
                                    'DERECHOS_ADUANA' => 'Derechos de Aduana',
                                    'IMPUESTO_IMPORTACION' => 'Impuesto Importación',
                                    'LOGISTICA' => 'Logística / Flete',
                                    'DOCUMENTACION' => 'Documentación',
                                    'OTROS_PREPARACION' => 'Otros',
                                ] as $v => $l)
                                <option value="{{ $v }}" {{ old('categoria', session('gasto_categoria')) == $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Origen *</label>
                        <select name="origen_tipo" required class="form-input">
                            @foreach([
                                    'FACTURA_PROVEEDOR' => 'Factura Proveedor',
                                    'STOCK_REPUESTO' => 'Repuesto en Stock',
                                    'MANO_OBRA' => 'Mano de Obra',
                                    'ADUANA' => 'Aduana',
                                    'FLETE' => 'Flete',
                                    'SEGURO' => 'Seguro',
                                    'OTRO' => 'Otro',
                                ] as $v => $l)
                                <option value="{{ $v }}" {{ old('origen_tipo') == $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Repuesto (opcional)</label>
                        <select name="repuesto_id" class="form-input">
                            <option value="">— Ninguno —</option>
                            @foreach($repuestos as $r)
                                <option value="{{ $r->id }}" {{ old('repuesto_id') == $r->id ? 'selected' : '' }}>{{ $r->codigo }} — {{ Str::limit($r->descripcion, 40) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cantidad repuesto</label>
                        <input type="number" name="repuesto_cantidad" value="{{ old('repuesto_cantidad') }}" step="0.001" min="0" class="form-input">
                    </div>
                    <fieldset class="form-group" style="grid-column:1/-1">
                        <legend class="form-label mb-1.5">Moneda del gasto *</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach(['USD' => ['Dólar', 'us'], 'PYG' => ['Guaraní', 'py'], 'BRL' => ['Real', 'br']] as $m => [$nombre, $bandera])
                                <label class="cursor-pointer">
                                    <input type="radio" name="moneda" value="{{ $m }}" class="peer sr-only" @checked(old('moneda', session('gasto_moneda', 'USD')) === $m)>
                                    <div class="flex items-center gap-3 p-3 rounded-xl border-2 border-border bg-surface-2 transition-all hover:bg-surface-3 peer-checked:border-primary peer-checked:bg-primary/5 peer-focus-visible:ring-2 peer-focus-visible:ring-primary">
                                        <img src="https://flagcdn.com/w40/{{ $bandera }}.png" class="w-8 h-6 rounded object-cover" alt="">
                                        <div class="min-w-0">
                                            <span class="block text-sm font-bold text-foreground">{{ $m }} <span class="font-normal text-muted-foreground">· {{ $nombre }}</span></span>
                                            <span class="block text-xs text-muted-foreground" data-tasa-de="{{ $m }}">{{ $m === 'USD' ? 'Moneda base' : 'Cargando cotización…' }}</span>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <div class="form-group">
                        <label class="form-label" for="monto_moneda">Monto en <span data-moneda-actual>{{ old('moneda', session('gasto_moneda', 'USD')) }}</span> *</label>
                        <input type="number" id="monto_moneda" name="monto_moneda" value="{{ old('monto_moneda') }}" step="0.01" min="0.01" required class="form-input" inputmode="decimal">
                        <div class="text-xs mt-1 text-muted-foreground" id="equivalente-usd"></div>
                    </div>
                    <div class="form-group" id="tasa-group">
                        <label class="form-label" for="tasa_cambio">Cotización (<span data-moneda-actual>{{ old('moneda', session('gasto_moneda', 'USD')) }}</span> por 1 USD)</label>
                        <input type="number" id="tasa_cambio" name="tasa_cambio" value="{{ old('tasa_cambio') }}" step="any" min="0.01" class="form-input" inputmode="decimal">
                        <div class="text-xs mt-1 text-muted-foreground" id="tasa-fuente" aria-live="polite"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha del gasto *</label>
                        <input type="date" name="fecha_gasto" value="{{ old('fecha_gasto', now('America/Asuncion')->toDateString()) }}" required class="form-input">
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label" for="comprobante">Comprobante (factura, recibo o foto)</label>
                        <input type="file" id="comprobante" name="comprobante" accept=".pdf,.jpg,.jpeg,.png,.webp,image/*" class="form-input">
                        <div class="text-xs mt-1" style="color:var(--text-muted)">Opcional. PDF o imagen, máximo 5 MB.</div>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Descripción / Observaciones</label>
                        <textarea name="descripcion" rows="2" class="form-input">{{ old('descripcion') }}</textarea>
                    </div>
                </div>
            {{-- El modal de confirmación envía con form.submit(), que no incluye el botón pulsado: por eso este campo oculto. --}}
            <input type="hidden" name="otro" value="1" id="gasto-otro" disabled>
            <div style="display:flex;flex-wrap:wrap;gap:.75rem;justify-content:flex-end;padding-top:1.25rem;margin-top:1rem;border-top:1px solid var(--border)">
                {{-- El principal va primero en el DOM para que Enter lo use; `order` lo muestra a la derecha. --}}
                <button type="submit" class="btn btn-primary" style="order:3" onclick="document.getElementById('gasto-otro').disabled = true">Registrar gasto</button>
                <a href="{{ route('vehicles.show', $vehiculo->id) }}" class="btn btn-ghost">Cancelar</a>
                <button type="submit" name="otro" value="1" class="btn btn-ghost" onclick="document.getElementById('gasto-otro').disabled = false">Guardar y registrar otro</button>
            </div>
        </form>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const form = document.getElementById('monto_moneda').form;
            const tasaInput = document.getElementById('tasa_cambio');
            const montoInput = document.getElementById('monto_moneda');
            const fechaInput = form.querySelector('input[name="fecha_gasto"]');
            const fuenteEl = document.getElementById('tasa-fuente');
            let rates = {};
            let detalle = {};
            // Tras un error de validación se respeta la cotización que el usuario había escrito.
            let tasaManual = @json(filled(old('tasa_cambio')));

            const fmt = (n, d) => Number(n).toLocaleString('es-PY', { minimumFractionDigits: d, maximumFractionDigits: d });
            const moneda = () => form.querySelector('input[name="moneda"]:checked')?.value || 'USD';

            function render() {
                const m = moneda();
                const esUsd = m === 'USD';
                document.getElementById('tasa-group').style.display = esUsd ? 'none' : '';
                tasaInput.disabled = esUsd;
                tasaInput.required = !esUsd;
                if (!esUsd && !tasaManual) tasaInput.value = rates[m] ?? '';
                form.querySelectorAll('[data-moneda-actual]').forEach(el => el.textContent = m);

                for (const k of ['PYG', 'BRL']) {
                    const el = form.querySelector(`[data-tasa-de="${k}"]`);
                    el.textContent = rates[k] ? `1 USD = ${fmt(rates[k], k === 'PYG' ? 0 : 2)}` : 'Sin cotización';
                }

                const det = detalle[m];
                fuenteEl.textContent = tasaManual ? 'Cotización modificada a mano.'
                    : det ? `Fuente: ${det.fuente} · ${new Date(det.fecha + 'T00:00').toLocaleDateString('es-PY')}`
                    : 'Sin cotización registrada: ingrésala a mano.';
                const monto = parseFloat(montoInput.value) || 0;
                const tasa = esUsd ? 1 : (parseFloat(tasaInput.value) || 0);
                document.getElementById('equivalente-usd').textContent =
                    !esUsd && monto > 0 && tasa > 0 ? `Equivale a USD ${fmt(monto / tasa, 2)}` : '';
            }

            async function cargarCotizacion() {
                try {
                    const res = await fetch(`{{ route('api.cotizaciones.today') }}?fecha=${encodeURIComponent(fechaInput.value)}`,
                        { headers: { Accept: 'application/json' } });
                    if (!res.ok) throw new Error(res.status);
                    const d = await res.json();
                    // Solo se usan las monedas con tasa registrada (las demás llegan como 1 por compatibilidad).
                    detalle = d.detalle || {};
                    rates = {};
                    for (const k of ['PYG', 'BRL']) if (detalle[k]) rates[k] = d[k];
                } catch (e) {
                    rates = {};
                    detalle = {};
                }
                render();
            }

            form.querySelectorAll('input[name="moneda"]').forEach(r => r.addEventListener('change', () => {
                tasaManual = false;
                render();
            }));
            tasaInput.addEventListener('input', () => { tasaManual = true; render(); });
            montoInput.addEventListener('input', render);
            fechaInput.addEventListener('change', () => { tasaManual = false; cargarCotizacion(); });

            render();
            cargarCotizacion();
        })();
    </script>
    @endpush
@endsection
