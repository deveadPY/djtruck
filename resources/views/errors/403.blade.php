@extends('layouts.app')

@section('title', 'Acceso Denegado')

@section('content')
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;text-align:center;">
    <div style="margin-bottom:1rem;color:var(--text-muted,#94a3b8)"><svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg></div>
    <h1 style="font-size:2rem;font-weight:700;color:var(--text);margin-bottom:.5rem;">Acceso Denegado</h1>
    <p style="color:var(--text-muted);font-size:1rem;margin-bottom:2rem;max-width:400px;">
        No tenés permisos para acceder a esta sección. Contactá al administrador del sistema si necesitás acceso.
    </p>
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
       class="btn btn-primary">
        Volver
    </a>
</div>
@endsection
