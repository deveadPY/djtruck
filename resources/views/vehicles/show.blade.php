@extends('layouts.app')
@section('title', 'Detalle Vehículo')
@section('page-title', 'Detalle de Vehículo')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <a href="{{ route('vehicles.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            Volver
        </a>
        <div class="flex gap-2">
            <a href="{{ route('vehicles.edit', $vehiculo->id) }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                Editar
            </a>
            <a href="{{ route('facturas.create', ['vehiculo_id' => $vehiculo->id]) }}" class="btn btn-ghost text-accent">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                Registrar Gasto
            </a>
            <form method="POST" action="{{ route('vehicles.destroy', $vehiculo->id) }}" class="inline" onsubmit="return confirm('¿Eliminar vehículo de forma permanente?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    Eliminar
                </button>
            </form>
        </div>
    </div>

    <div class="erp-card">
        <div class="erp-card-header">
            <div class="flex items-center gap-3">
                <h2>{{ $vehiculo->marca }} {{ $vehiculo->modelo }} — {{ $vehiculo->anio }}</h2>
                @php $cls = match ($vehiculo->estado) { 'DISPONIBLE' => 'badge-disponible', 'EN_PREPARACION' => 'badge-preparacion', 'TOMA' => 'badge-toma', default => 'badge-vendido'}; @endphp
                <span class="badge-status {{ $cls }}">{{ $vehiculo->estado }}</span>
            </div>
        </div>
        <div class="erp-card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider mb-4" style="color: var(--text-muted);">Datos del vehículo</h3>
                    @foreach([
                            'Chasis' => $vehiculo->numero_chasis,
                            'Color' => $vehiculo->color,
                            'Tipo' => $vehiculo->tipo_vehiculo,
                            'Kilometraje' => number_format($vehiculo->kilometraje, 2, ',', '.') . ' km',
                        ] as $label => $value)
                        <div class="flex justify-between py-2.5 border-b text-sm" style="border-color: var(--border);">
                            <span style="color: var(--text-muted);">{{ $label }}</span>
                            <span>{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider mb-4" style="color: var(--text-muted);">Financiero</h3>
                    @foreach([
                            'Moneda costo' => $vehiculo->moneda_costo,
                            'Costo origen' => '$ ' . number_format($vehiculo->costo_origen_usd, 2, ',', '.'),
                            'Total gastos' => '$ ' . number_format($vehiculo->total_gastos_usd ?? 0, 2, ',', '.'),
                            'Valor libro' => '$ ' . number_format(($vehiculo->costo_origen_usd + ($vehiculo->total_gastos_usd ?? 0)), 2, ',', '.'),
                            'Precio contado' => $vehiculo->precio_contado_usd ? '$ ' . number_format($vehiculo->precio_contado_usd, 2, ',', '.') : '—',
                            'Precio cuotas' => $vehiculo->precio_cuotas_usd ? '$ ' . number_format($vehiculo->precio_cuotas_usd, 2, ',', '.') : '—',
                        ] as $label => $value)
                        <div class="flex justify-between py-2.5 border-b text-sm" style="border-color: var(--border);">
                            <span style="color: var(--text-muted);">{{ $label }}</span>
                            <span class="text-accent">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Galería de Imágenes --}}
    @if(isset($imagenes) && $imagenes->count() > 0)
    <div class="erp-card mt-5">
        <div class="erp-card-header">
            <h2 class="flex items-center gap-2">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>
                Galería ({{ $imagenes->count() }} imágenes)
            </h2>
        </div>
        <div class="erp-card-body">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @foreach($imagenes as $img)
                <div class="rounded-xl overflow-hidden border cursor-pointer aspect-[4/3] hover:opacity-80 transition-opacity" style="border-color: var(--border);" onclick="openLightbox('{{ asset($img->ruta) }}')">
                    <img src="{{ asset($img->ruta) }}" class="w-full h-full object-cover" alt="{{ $img->nombre_original }}">
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @include('partials.documentos', [
        'documentos' => $documentos ?? collect(),
        'documentableType' => 'vehiculos',
        'documentableId' => $vehiculo->id,
    ])

    {{-- Lightbox --}}
    <div id="lightbox" class="fixed inset-0 z-[99999] items-center justify-center cursor-pointer hidden" style="background:rgba(0,0,0,.85);" onclick="closeLightbox()">
        <img id="lightboxImg" class="max-w-[90%] max-h-[90%] rounded-xl shadow-2xl">
    </div>
    @push('scripts')
    <script>
        function openLightbox(src) {
            document.getElementById('lightboxImg').src = src;
            const lb = document.getElementById('lightbox');
            lb.classList.remove('hidden');
            lb.style.display = 'flex';
        }
        function closeLightbox() {
            const lb = document.getElementById('lightbox');
            lb.style.display = 'none';
            lb.classList.add('hidden');
        }
    </script>
    @endpush
@endsection
