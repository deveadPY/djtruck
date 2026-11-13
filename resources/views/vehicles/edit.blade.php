@extends('layouts.app')
@section('title', 'Editar Vehículo')
@section('page-title', 'Editar Vehículo')

@section('content')
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <a href="{{ route('vehicles.show', $vehiculo->id) }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Volver a la ficha
        </a>
        <p class="text-sm text-muted-foreground">
            <span class="font-semibold text-foreground">{{ $vehiculo->marca }} {{ $vehiculo->modelo }}</span>
            · Chasis {{ $vehiculo->numero_chasis }}
        </p>
    </div>

    <form id="vehicleEditForm" method="POST" action="{{ route('vehicles.update', $vehiculo->id) }}"
        enctype="multipart/form-data" data-confirm="Confirmar actualización de vehículo">
        @csrf
        @method('PUT')
        @include('vehicles._formulario')
    </form>

    {{-- Acciones de fotos ya cargadas: formularios propios, enviados por los botones del formulario principal (atributo form). --}}
    @foreach($imagenes as $img)
        @unless($img->es_portada)
            <form id="img-portada-{{ $img->id }}" method="POST" action="{{ route('vehicles.imagenes.portada', [$vehiculo->id, $img->id]) }}" class="hidden">
                @csrf
            </form>
        @endunless
        <form id="img-borrar-{{ $img->id }}" method="POST" action="{{ route('vehicles.imagenes.destroy', [$vehiculo->id, $img->id]) }}" class="hidden"
            data-danger-confirm="La foto se quitará del vehículo y del sitio web." data-danger-title="¿Eliminar esta foto?" data-danger-action-label="Eliminar foto">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endsection
