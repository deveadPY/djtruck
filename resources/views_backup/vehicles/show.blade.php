@extends('layouts.app')
@section('title', 'Detalle Vehículo — ERP Camiones')
@section('page-title', '🚛 Detalle de Vehículo')

@section('content')
    <div style="margin-bottom:1rem">
        <a href="{{ route('vehicles.index') }}" class="btn btn-ghost">← Volver</a>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('vehicles.edit', $vehiculo->id) }}" class="btn btn-ghost" style="padding:.4rem .8rem;font-size:.875rem">✏️ Editar</a>
            <a href="{{ route('facturas.create', ['vehiculo_id' => $vehiculo->id]) }}" class="btn btn-ghost" style="padding:.4rem .8rem;font-size:.875rem;color:var(--accent)">💸 Registrar Gasto</a>
            <form method="POST" action="{{ route('vehicles.destroy', $vehiculo->id) }}" style="display:inline" onsubmit="return confirm('¿Eliminar vehículo de forma permanente?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost" style="padding:.4rem .8rem;font-size:.875rem;color:var(--danger)">🗑️ Eliminar</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <div style="display:flex;align-items:center;gap:1rem">
                <h2>{{ $vehiculo->marca }} {{ $vehiculo->modelo }} — {{ $vehiculo->año }}</h2>
                @php $cls = match ($vehiculo->estado) { 'DISPONIBLE' => 'badge-disponible', 'EN_PREPARACION' => 'badge-preparacion', 'TOMA' => 'badge-toma', default => 'badge-vendido'}; @endphp
                <span class="badge-status {{ $cls }}">{{ $vehiculo->estado }}</span>
            </div>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem">
                <div>
                    <h3 style="font-size:.8rem; color:var(--text-muted); margin-bottom:1rem; text-transform:uppercase">Datos
                        del vehículo</h3>
                    @foreach([
                            'Chasis' => $vehiculo->numero_chasis,
                            'Color' => $vehiculo->color,
                            'Tipo' => $vehiculo->tipo_vehiculo,
                            'Kilometraje' => number_format($vehiculo->kilometraje, 2, ',', '.') . ' km',
                        ] as $label => $value)
                        <div style="display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid var(--border); font-size:.875rem">
                            <span style="color:var(--text-muted)">{{ $label }}</span>
                            <span>{{ $value }}</span>

                                            </div>
                    @endforeach
                </div>
                <div>
                    <h3 style="font-size:.8rem; color:var(--text-muted); margin-bottom:1rem; text-transform:uppercase">Financiero</h3>
                    @foreach([
                            'Moneda costo' => $vehiculo->moneda_costo,
                            'Costo origen' => '$ ' . number_format($vehiculo->costo_origen_usd, 2, ',', '.'),
                            'Total gastos' => '$ ' . number_format($vehiculo->total_gastos_usd ?? 0, 2, ',', '.'),
                            'Valor libro' => '$ ' . number_format(($vehiculo->costo_origen_usd + ($vehiculo->total_gastos_usd ?? 0)), 2, ',', '.'),
                        ] as $label => $value)
                        <div style="display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid var(--border); font-size:.875rem">
                            <span style="color:var(--text-muted)">{{ $label }}</span>
                            <span style="color:var(--accent)">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Galería de Imágenes --}}
    @if(isset($imagenes) && $imagenes->count() > 0)
    <div class="card" style="margin-top:1.25rem">
        <div class="card-header"><h2>📸 Galería ({{ $imagenes->count() }} imágenes)</h2></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem">
                @foreach($imagenes as $img)
                <div style="border-radius:10px;overflow:hidden;border:1px solid var(--border);aspect-ratio:4/3;cursor:pointer" onclick="openLightbox('{{ asset($img->ruta) }}')">
                    <img src="{{ asset($img->ruta) }}" style="width:100%;height:100%;object-fit:cover" alt="{{ $img->nombre_original }}">
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
    <div id="lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:99999;align-items:center;justify-content:center;cursor:pointer" onclick="closeLightbox()">
        <img id="lightboxImg" style="max-width:90%;max-height:90%;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.5)">
    </div>
    @push('scripts')
    <script>
        function openLightbox(src) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('lightbox').style.display = 'flex';
        }
        function closeLightbox() {
            document.getElementById('lightbox').style.display = 'none';
        }
    </script>
    @endpush
@endsection
