@extends('layouts.app')
@section('title', 'Cargar Factura')
@section('page-title', '💸 Cargar Factura / Gasto')

@push('styles')
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .4rem
        }

        label {
            font-size: .78rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .05em
        }

        input,
        select,
        textarea {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .65rem .9rem;
            color: var(--text);
            font-family: inherit;
            font-size: .875rem;
            outline: none;
            width: 100%;
            transition: border-color .2s
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary)
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, .5);
            justify-content: center;
            align-items: center;
            z-index: 1000
        }

        .modal.open {
            display: flex
        }

        .modal-content {
            background: var(--surface);
            padding: 2rem;
            border-radius: 12px;
            width: 100%;
            max-width: 500px
        }
    </style>
@endpush

@section('content')
    @if($errors->any())
        <div
            style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:var(--danger);padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:.85rem">
            {{ $errors->first() }}
    </div>@endif

    <div style="margin-bottom:1rem"><a href="{{ route('facturas.index') }}" class="btn btn-ghost">← Volver</a></div>

    <div class="card">
        <div class="card-header">
            <h2>Detalles de la Factura</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('facturas.store') }}" data-confirm="Confirmar registro de factura">
                @csrf
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1">
                        <label>Proveedor *</label>
                        <div style="display:flex;gap:.5rem">
                            <select name="proveedor_id" id="proveedor_sel" required style="flex:1">
                                <option value="">— Seleccionar —</option>
                                @foreach($proveedores as $p)
                                    <option value="{{ $p->id }}" {{ old('proveedor_id') == $p->id ? 'selected' : '' }}>
                                        {{ $p->razon_social }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-ghost" style="border:1px solid var(--border)"
                                onclick="document.getElementById('provModal').classList.add('open')">+ Nuevo</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>N° de Factura / Ticket *</label>
                        <input type="text" name="numero_factura" value="{{ old('numero_factura') }}" required
                            maxlength="50">
                    </div>
                    <div class="form-group">
                        <label>Fecha del gasto *</label>
                        <input type="date" name="fecha_factura" value="{{ old('fecha_factura', date('Y-m-d')) }}" required>
                    </div>

                    <div class="form-group">
                        <label>Destino del Gasto *</label>
                        <select name="destino" id="destino_sel" required onchange="toggleDestino()">
                            <option value="GASTO_OPERATIVO" {{ old('destino', request('vehiculo_id') ? 'VEHICULO' : 'GASTO_OPERATIVO') == 'GASTO_OPERATIVO' ? 'selected' : '' }}>🏢 Local /
                                Administrativo</option>
                            <option value="VEHICULO" {{ old('destino', request('vehiculo_id') ? 'VEHICULO' : '') == 'VEHICULO' ? 'selected' : '' }}>🚛 Vehículo (Costo)
                            </option>
                            <option value="MIXTO" {{ old('destino') == 'MIXTO' ? 'selected' : '' }}>Mixto</option>
                        </select>
                    </div>

                    <div class="form-group" id="vehiculo_container" style="display:none">
                        <label>Vehículo asociado *</label>
                        <select name="vehiculo_id" id="vehiculo_id">
                            <option value="">— Seleccionar Camión —</option>
                            @foreach($vehiculos as $v)
                                <option value="{{ $v->id }}" {{ old('vehiculo_id', request('vehiculo_id')) == $v->id ? 'selected' : '' }}>{{ $v->marca }}
                                    {{ $v->modelo }} ({{ substr($v->numero_chasis, -6) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group" id="categoria_vehiculo_container" style="display:none">
                        <label>Categoría (Solo Vehículos) *</label>
                        <select name="categoria_gasto" id="categoria_gasto">
                            @foreach([
                                'REPARACION_MECANICA' => 'Reparación Mecánica',
                                'CHAPERIA_PINTURA' => 'Chapería y Pintura',
                                'ELECTRICIDAD' => 'Electricidad',
                                'NEUMATICOS' => 'Neumáticos',
                                'DERECHOS_ADUANA' => 'Derechos de Aduana',
                                'IMPUESTO_IMPORTACION' => 'Impuesto de Importación',
                                'LOGISTICA' => 'Logística y Transporte',
                                'DOCUMENTACION' => 'Documentación / Escribanía',
                                'OTROS_PREPARACION' => 'Otros (Preparación)'
                            ] as $val => $label)
                                <option value="{{ $val }}" {{ old('categoria_gasto', 'OTROS_PREPARACION') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="cuenta_container">
                        <label>Concepto / Cuenta *</label>
                        <input type="text" name="cuenta_gasto" id="cuenta_gasto" value="{{ old('cuenta_gasto') }}"
                            placeholder="Ej: Luz, Agua, Alquiler, Reparación">
                    </div>

                    <div class="form-group" style="grid-column:1/-1">
                        <hr style="border-color:var(--border)">
                    </div>

                    <div class="form-group">
                        <label>Moneda *</label>
                        <select name="moneda" required>
                            @foreach(['USD', 'PYG', 'BRL'] as $m)
                                <option value="{{ $m }}" {{ old('moneda', 'PYG') == $m ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subtotal *</label>
                        <input type="number" name="subtotal" value="{{ old('subtotal') }}" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Impuestos</label>
                        <input type="number" name="impuestos" value="{{ old('impuestos', 0) }}" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Total equivalente en USD *</label>
                        <input type="number" name="total_usd" value="{{ old('total_usd') }}" step="0.01" min="0" required
                            title="El costo siempre debe reflejarse en USD para el balance.">
                    </div>

                    <div class="form-group">
                        <label>Estado *</label>
                        <select name="estado" required>
                            @foreach(['PENDIENTE', 'APROBADA', 'PAGADA'] as $val)
                                <option value="{{ $val }}" {{ old('estado', 'PAGADA') == $val ? 'selected' : '' }}>{{ $val }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label>Observaciones / Detalle</label>
                        <textarea name="descripcion" rows="2">{{ old('descripcion') }}</textarea>
                    </div>
                </div>
                <div
                    style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:1.25rem;margin-top:1rem;border-top:1px solid var(--border)">
                    <a href="{{ route('facturas.index') }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary">💾 Registrar gasto</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Proveedor Rápidos -->
    <div class="modal" id="provModal">
        <div class="modal-content">
            <h3 style="margin-bottom:1rem">Añadir Proveedor Rápido</h3>
            <div class="form-group" style="margin-bottom:1rem">
                <label>Razón Social *</label>
                <input type="text" id="prov_rs" required>
            </div>
            <div class="form-group" style="margin-bottom:1rem">
                <label>Tipo *</label>
                <select id="prov_tipo">
                    <option value="OTRO">Op. General / Varios</option>
                    <option value="DISTRIBUIDOR">Repuestos / Insumos</option>
                    <option value="SERVICIO">Servicios Técnicos</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:1.5rem">
                <label>RUC (Opcional)</label>
                <input type="text" id="prov_ruc">
            </div>
            <div style="display:flex;gap:.5rem;justify-content:flex-end">
                <button class="btn btn-ghost"
                    onclick="document.getElementById('provModal').classList.remove('open')">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarProvAjax()">Completar</button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function toggleDestino() {
                const dest = document.getElementById('destino_sel').value;
                const vehCont = document.getElementById('vehiculo_container');
                const vehEl = document.getElementById('vehiculo_id');
                const catCont = document.getElementById('categoria_vehiculo_container');
                const cta = document.getElementById('cuenta_gasto');

                if (dest === 'VEHICULO') {
                    vehCont.style.display = 'flex';
                    catCont.style.display = 'flex';
                    vehEl.required = true;
                    cta.placeholder = 'Concepto (Ej: Chapería, Flete...)';
                } else {
                    vehCont.style.display = 'none';
                    catCont.style.display = 'none';
                    vehEl.required = false;
                    if (!document.querySelector('option[selected][value="' + vehEl.value + '"]')) {
                        vehEl.value = '';
                    }
                    cta.placeholder = 'Ej: Luz, Agua, Alquiler...';
                }
            }
            toggleDestino();

            async function guardarProvAjax() {
                const rs = document.getElementById('prov_rs').value;
                if (!rs) return alert('La Razón Social es obligatoria');

                try {
                    const res = await fetch("{{ route('proveedores.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            razon_social: rs,
                            tipo: document.getElementById('prov_tipo').value,
                            ruc_rut_nit: document.getElementById('prov_ruc').value,
                            pais: 'PY',
                            moneda_principal: 'PYG'
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        const sel = document.getElementById('proveedor_sel');
                        const opt = new Option(data.proveedor.razon_social, data.proveedor.id, true, true);
                        sel.add(opt);
                        document.getElementById('provModal').classList.remove('open');
                    } else {
                        alert('Error al guardar: verifique los datos');
                    }
                } catch (e) { alert('Error de red'); }
            }

            let currentRates = { PYG: 1, BRL: 1 };

            async function fetchRates() {
                const fecha = document.querySelector('input[name="fecha_factura"]').value;
                try {
                    const res = await fetch(`{{ route('api.cotizaciones.today') }}?fecha=${fecha}`);
                    const data = await res.json();
                    currentRates = data;
                    calcularTotalUsd();
                } catch (e) { console.error('Error fetching rates', e); }
            }

            function calcularTotalUsd() {
                const sub = parseFloat(document.querySelector('input[name="subtotal"]').value) || 0;
                const imp = parseFloat(document.querySelector('input[name="impuestos"]').value) || 0;
                const moneda = document.querySelector('select[name="moneda"]').value;
                const totalMoneda = sub + imp;
                let totalUsd = totalMoneda;

                if (moneda === 'PYG') {
                    const rate = currentRates.PYG || 1;
                    totalUsd = totalMoneda / rate;
                } else if (moneda === 'BRL') {
                    const rate = currentRates.BRL || 1;
                    totalUsd = totalMoneda / rate;
                }

                document.querySelector('input[name="total_usd"]').value = totalUsd.toFixed(2);
            }

            document.querySelector('input[name="fecha_factura"]').addEventListener('change', fetchRates);
            document.querySelector('select[name="moneda"]').addEventListener('change', calcularTotalUsd);
            document.querySelector('input[name="subtotal"]').addEventListener('input', calcularTotalUsd);
            document.querySelector('input[name="impuestos"]').addEventListener('input', calcularTotalUsd);

            // Fetch initial rates on load
            fetchRates();
        </script>
    @endpush
@endsection