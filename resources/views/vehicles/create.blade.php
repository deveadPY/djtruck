@extends('layouts.app')
@section('title', 'Nuevo Vehículo')
@section('page-title', 'Registrar Vehículo')

@section('content')
    <div class="mb-4">
        <a href="{{ route('vehicles.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Volver a vehículos
        </a>
    </div>

    <form method="POST" action="{{ route('vehicles.store') }}" data-confirm="Confirmar registro de vehículo"
        enctype="multipart/form-data">
        @csrf
        @include('vehicles._formulario', ['vehiculo' => null])
    </form>
@endsection
