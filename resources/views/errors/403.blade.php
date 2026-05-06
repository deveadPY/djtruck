@extends('layouts.app')

@section('title', 'Acceso Denegado')

@section('content')
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;text-align:center;">
    <div style="font-size:4rem;margin-bottom:1rem;">🔒</div>
    <h1 style="font-size:2rem;font-weight:700;color:#e2e8f0;margin-bottom:.5rem;">Acceso Denegado</h1>
    <p style="color:#aaa;font-size:1rem;margin-bottom:2rem;max-width:400px;">
        No tenés permisos para acceder a esta sección. Contactá al administrador del sistema si necesitás acceso.
    </p>
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
       style="background:#6c63ff;color:#fff;padding:.6rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:600;">
        Volver
    </a>
</div>
@endsection
