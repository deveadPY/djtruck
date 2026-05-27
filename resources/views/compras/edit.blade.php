@extends('layouts.app')
@section('title', 'Editar Compra')
@section('page-title', 'Editar Compra #' . $compra->id)

@section('content')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<div class="mb-6">
    <a href="{{ route('compras.show', $compra->id) }}" class="btn btn-ghost">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Volver al Detalle
    </a>
</div>

<form action="{{ route('compras.update', $compra->id) }}" method="POST" id="compraForm" class="space-y-6" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="erp-card">
                <div class="erp-card-header">
                    <h2 class="text-base font-bold">Información de la Compra</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Proveedor <span class="text-red-500">*</span></label>
                            <select name="proveedor_id" id="proveedor_id_selector" class="form-input" required>
                                <option value="">Seleccione un proveedor</option>
                                @foreach($proveedores as $p)
                                    <option value="{{ $p->id }}" {{ $compra->proveedor_id == $p->id ? 'selected' : '' }}>{{ $p->razon_social }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha de Compra <span class="text-red-500">*</span></label>
                            <input type="date" name="fecha_compra" value="{{ $compra->fecha_compra instanceof \Carbon\Carbon ? $compra->fecha_compra->format('Y-m-d') : $compra->fecha_compra }}" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Número de Factura</label>
                            <input type="text" name="numero_factura" value="{{ $compra->numero_factura }}" class="form-input" placeholder="001-001-0000000">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Caja de Origen (Capital)</label>
                            <input type="text" value="CAJA CAPITAL" readonly class="form-input bg-surface3 opacity-70 cursor-not-allowed">
                        </div>
                        <div class="form-group col-span-1 md:col-span-2">
                            <label class="form-label">Comprobantes / Facturas (PDF, Imágenes)</label>
                            <div class="flex items-center gap-3">
                                <label class="flex-1 cursor-pointer">
                                    <div class="relative group">
                                        <input type="file" name="adjuntos[]" multiple class="hidden" id="adjuntos" onchange="updateFileName(this)">
                                        <div class="form-input flex items-center gap-3 group-hover:border-primary transition-colors">
                                            <div class="bg-primary/20 p-1.5 rounded-lg text-primary">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                                                </svg>
                                            </div>
                                            <span id="file-name" class="text-sm text-slate-400">Seleccionar archivos...</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="erp-card">
                <div class="erp-card-header flex justify-between items-center">
                    <h2 class="text-base font-bold">Productos a Reponer</h2>
                    <button type="button" onclick="addItem()" class="btn btn-ghost btn-sm text-primary">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Añadir Producto
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="erp-table text-xs" id="itemsTable">
                        <thead>
                            <tr class="bg-surface2/50">
                                <th class="w-1/3">Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Compra (Moneda)</th>
                                <th>Precio Venta (USD)</th>
                                <th class="text-right">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="erp-card">
                <div class="erp-card-header">
                    <h2 class="text-base font-bold">Totales</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="form-group">
                        <label class="form-label">Moneda de Pago</label>
                        <select name="moneda_compra" id="moneda_compra" class="form-input" onchange="updateRates()">
                            <option value="USD" {{ $compra->moneda_compra == 'USD' ? 'selected' : '' }}>Dólar (USD)</option>
                            <option value="PYG" {{ $compra->moneda_compra == 'PYG' ? 'selected' : '' }}>Guaraní (PYG)</option>
                            <option value="BRL" {{ $compra->moneda_compra == 'BRL' ? 'selected' : '' }}>Real (BRL)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tasa de Cambio (a USD)</label>
                        <input type="number" name="tasa_cambio" id="tasa_cambio" value="{{ $compra->tasa_cambio }}" step="0.0001" class="form-input" oninput="calculateAll()">
                    </div>

                    <div class="pt-4 border-t border-border space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Subtotal USD:</span>
                            <span id="subtotalDisplayUsd" class="font-bold font-mono">$ 0.00</span>
                        </div>
                        <div class="flex justify-between text-base">
                            <span class="font-bold">TOTAL USD:</span>
                            <span id="totalDisplayUsd" class="font-bold font-mono text-accent text-lg">$ 0.00</span>
                        </div>
                        <div class="flex justify-between text-sm text-muted-foreground italic" id="totalMonedaContainer">
                            <span>Total en Moneda:</span>
                            <span id="totalDisplayMoneda" class="font-mono">0.00</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-full py-3 mt-4">
                        Guardar Cambios
                    </button>
                </div>
            </div>

            <div class="erp-card">
                <div class="erp-card-header">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Observaciones</h2>
                </div>
                <div class="p-4">
                    <textarea name="observaciones" rows="3" class="form-input text-sm" placeholder="Opcional...">{{ $compra->observaciones }}</textarea>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="itemRowTemplate">
    <tr class="item-row border-b border-border/50 hover:bg-surface2/30">
        <td class="p-2">
            <select name="items[INDEX][repuesto_id]" class="form-input text-xs w-full producto-select" required onchange="onProductChange(this)">
                <option value="">Seleccionar...</option>
                @foreach($productos as $prod)
                    <option value="{{ $prod->id }}"
                        data-proveedor="{{ $prod->proveedor_id }}"
                        data-compra="{{ $prod->costo_promedio_usd }}"
                        data-venta="{{ $prod->precio_venta_usd }}"
                        data-stock="{{ number_format($prod->stock_actual, 0) }}">
                        {{ $prod->codigo }} - {{ $prod->descripcion }}
                    </option>
                @endforeach
            </select>
        </td>
        <td class="p-2 w-24">
            <input type="number" name="items[INDEX][cantidad]" step="0.001" min="0.001" value="1" class="form-input text-xs text-center font-bold qty-input" required oninput="calculateAll()">
        </td>
        <td class="p-2 w-32">
            <input type="number" name="items[INDEX][precio_compra]" step="0.01" min="0" value="0" class="form-input text-xs text-right buy-price-input" required oninput="calculateAll()">
        </td>
        <td class="p-2 w-32">
            <input type="number" name="items[INDEX][precio_venta_sugerido]" step="0.01" min="0" value="0" class="form-input text-xs text-right sell-price-input">
        </td>
        <td class="p-2 text-right font-mono font-bold text-accent subtotal-cell">0.00</td>
        <td class="p-2 w-10">
            <button type="button" onclick="removeItem(this)" class="text-red-500 hover:bg-red-500/10 p-1 rounded">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </td>
    </tr>
</template>

@push('scripts')
<script>
    let itemIndex = 0;
    const itemsBody = document.getElementById('itemsBody');
    const template = document.getElementById('itemRowTemplate').innerHTML;
    let currentRates = { PYG: 1, BRL: 1 };

    // Preload existing items
    const existingItems = @json($items);

    function updateFileName(input) {
        const count = input.files.length;
        const fileName = count > 0 ? (count === 1 ? input.files[0].name : count + ' archivos seleccionados') : 'Seleccionar archivos...';
        document.getElementById('file-name').textContent = fileName;
    }

    async function fetchRates() {
        try {
            const fecha = new Date().toISOString().split('T')[0];
            const res = await fetch(`{{ route('api.cotizaciones.today') }}?fecha=${fecha}`);
            const data = await res.json();
            currentRates = data;
        } catch(e) { console.error('Error fetching rates', e); }
    }

    function removeItem(btn) {
        btn.closest('tr').remove();
        calculateAll();
    }

    function onProductChange(select) {
        const row = select.closest('tr');
        const ts = select.tomselect;
        if (!ts) return;

        const val = ts.getValue();
        const options = Array.from(select.querySelectorAll('option'));
        const opt = options.find(o => o.value == val);

        if (opt) {
            const costoUsd = parseFloat(opt.getAttribute('data-compra')) || 0;
            const ventaUsd = parseFloat(opt.getAttribute('data-venta')) || 0;
            const moneda = document.getElementById('moneda_compra').value;
            const tasa = parseFloat(document.getElementById('tasa_cambio').value) || 1;

            row.querySelector('.sell-price-input').value = ventaUsd.toFixed(2);
            let precioCompraMo = (moneda === 'USD') ? costoUsd : (costoUsd * tasa);
            row.querySelector('.buy-price-input').value = precioCompraMo.toFixed(2);
        } else {
            row.querySelector('.sell-price-input').value = 0;
            row.querySelector('.buy-price-input').value = 0;
        }

        calculateAll();
    }

    function initTomSelect(el) {
        if (el.tomselect) return el.tomselect;
        const ts = new TomSelect(el, {
            create: false,
            sortField: { field: "text", direction: "asc" },
            placeholder: "Buscar por código o nombre...",
            allowEmptyOption: true,
            render: {
                option: function(data, escape) {
                    return `<div class="py-3 px-4 border-b border-white/5 hover:bg-primary/10 transition-all group">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="font-bold text-accent-light text-[0.75rem] font-mono tracking-wider group-hover:translate-x-1 transition-transform origin-left">${escape(data.codigo || data.text.split(' - ')[0])}</span>
                            <span class="text-[0.6rem] px-2 py-0.5 rounded-full bg-surface3 text-white border border-white/10 font-bold uppercase tracking-wider">STOCK: ${escape(data.stock || '0')}</span>
                        </div>
                        <div class="text-[0.8rem] leading-snug text-slate-300 group-hover:text-white transition-colors">
                            ${escape(data.descripcion || data.text.split(' - ')[1] || '')}
                        </div>
                    </div>`;
                },
                item: function(data, escape) {
                    return `<div class="flex items-center gap-2 py-0.5">
                        <span class="bg-accent/20 text-accent-light px-2 py-0.5 rounded text-[0.7rem] font-mono font-bold border border-accent/30">${escape(data.codigo || data.text.split(' - ')[0])}</span>
                        <span class="text-[0.75rem] font-semibold text-white truncate max-w-[200px]">${escape((data.descripcion || data.text.split(' - ')[1] || '').substring(0, 40))}</span>
                    </div>`;
                }
            }
        });
        el.tomselect = ts;
        return ts;
    }

    function addItem() {
        const rowHTML = template.replace(/INDEX/g, itemIndex++);
        itemsBody.insertAdjacentHTML('beforeend', rowHTML);
        const newRow = itemsBody.lastElementChild;
        const newSelect = newRow.querySelector('.producto-select');
        initTomSelect(newSelect);
        newRow.classList.add('animate-pulse', 'bg-primary/5');
        setTimeout(() => newRow.classList.remove('animate-pulse', 'bg-primary/5'), 1000);
        filterProductsByProvider();
        calculateAll();
    }

    function addExistingItem(itemData, idx) {
        const rowHTML = template.replace(/INDEX/g, idx);
        itemsBody.insertAdjacentHTML('beforeend', rowHTML);
        const newRow = itemsBody.lastElementChild;
        const select = newRow.querySelector('.producto-select');
        initTomSelect(select);

        // Set values
        setTimeout(() => {
            if (select.tomselect) {
                select.tomselect.setValue(String(itemData.repuesto_id));
            }
            newRow.querySelector('.qty-input').value = itemData.cantidad;
            newRow.querySelector('.buy-price-input').value = itemData.precio_compra_moneda;
            if (itemData.precio_venta_sugerido_usd) {
                newRow.querySelector('.sell-price-input').value = itemData.precio_venta_sugerido_usd;
            }
        }, 50);
    }

    function filterProductsByProvider() {
        const proveedorId = document.getElementById('proveedor_id_selector').value;
        const selects = document.querySelectorAll('.producto-select');
        selects.forEach(select => {
            if (select.tomselect) {
                const ts = select.tomselect;
                const currentValue = ts.getValue();
                const options = select.querySelectorAll('option');
                ts.clearOptions();
                options.forEach(opt => {
                    if (!opt.value) return;
                    const optProvId = opt.getAttribute('data-proveedor');
                    if (!proveedorId || optProvId == proveedorId) {
                        ts.addOption({ value: opt.value, text: opt.text });
                    }
                });
                ts.refreshOptions(false);
            }
        });
    }

    function updateRates() {
        const moneda = document.getElementById('moneda_compra').value;
        const tasaInput = document.getElementById('tasa_cambio');
        if (moneda === 'USD') {
            tasaInput.value = 1;
            tasaInput.readOnly = true;
            tasaInput.classList.add('bg-surface3', 'opacity-70');
        } else {
            if (!tasaInput.value || tasaInput.value == '1') {
                tasaInput.value = currentRates[moneda] || (moneda === 'PYG' ? 7300 : 5);
            }
            tasaInput.readOnly = false;
            tasaInput.classList.remove('bg-surface3', 'opacity-70');
        }
        calculateAll();
    }

    function calculateAll() {
        const rows = document.querySelectorAll('.item-row');
        const moneda = document.getElementById('moneda_compra').value;
        const tasa = parseFloat(document.getElementById('tasa_cambio').value) || 1;
        let totalMoneda = 0;
        let totalUsd = 0;

        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
            const price = parseFloat(row.querySelector('.buy-price-input').value) || 0;
            const subtotalMoneda = qty * price;
            let subtotalUsd = (moneda === 'USD') ? subtotalMoneda : (subtotalMoneda / tasa);
            row.querySelector('.subtotal-cell').innerText = subtotalMoneda.toLocaleString('es-PY', { minimumFractionDigits: 2 });
            totalMoneda += subtotalMoneda;
            totalUsd += subtotalUsd;
        });

        document.getElementById('subtotalDisplayUsd').innerText = '$ ' + totalUsd.toLocaleString('es-PY', { minimumFractionDigits: 2 });
        document.getElementById('totalDisplayUsd').innerText = '$ ' + totalUsd.toLocaleString('es-PY', { minimumFractionDigits: 2 });
        document.getElementById('totalDisplayMoneda').innerText = totalMoneda.toLocaleString('es-PY', { minimumFractionDigits: 2 }) + ' ' + moneda;

        const container = document.getElementById('totalMonedaContainer');
        container.style.display = (moneda === 'USD') ? 'none' : 'flex';
    }

    document.addEventListener('DOMContentLoaded', () => {
        fetchRates().then(() => {
            new TomSelect('#proveedor_id_selector', {
                create: false,
                placeholder: "Buscar proveedor...",
                allowEmptyOption: false
            });

            // Load existing items
            if (existingItems.length > 0) {
                existingItems.forEach((item, i) => {
                    addExistingItem(item, i);
                    itemIndex = i + 1;
                });
            } else {
                addItem();
            }

            setTimeout(() => {
                updateRates();
                calculateAll();
            }, 150);
        });
    });
</script>

<style>
    .ts-wrapper.form-input { padding: 0 !important; border: none !important; background: transparent !important; }
    .ts-control {
        @apply bg-surface2 border border-border rounded-xl text-sm px-4 py-3 transition-all !important;
        background-color: var(--surface2) !important;
        border-color: var(--border) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
    }
    .ts-control input { color: #ffffff !important; font-size: 0.875rem !important; }
    .ts-control input::placeholder { color: #94a3b8 !important; }
    .ts-wrapper.focus .ts-control {
        @apply border-primary ring-4 ring-primary/20 !important;
        border-color: var(--primary) !important;
    }
    .ts-dropdown {
        @apply bg-[#1e2230] border-white/10 rounded-2xl mt-2 overflow-hidden shadow-2xl z-[1000] !important;
        background-color: #1e2230 !important;
        border-color: rgba(255,255,255,0.1) !important;
        animation: ts-dropdown-in 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .ts-dropdown .option.active { background-color: rgba(108, 99, 255, 0.15) !important; color: #ffffff !important; }
    .ts-dropdown .option { color: #e2e8f0 !important; }
    .item-row td { vertical-align: middle; }
</style>
@endpush
@endsection
