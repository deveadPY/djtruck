@extends('layouts.app')
@section('title', 'Actualizar Cotización')
@section('page-title', 'Cambio del Día')

@section('content')
    <div class="flex flex-col items-center justify-center min-h-[60vh]">
        <div style="width: 100%; max-width: 500px;">
            <div style="margin-bottom:1.5rem">
                <a href="{{ route('cotizaciones.index') }}" class="btn btn-ghost">← Volver al historial</a>
            </div>

            <div class="erp-card shadow-2xl" style="border: 1px solid var(--primary-light);">
                <div class="erp-card-header text-center py-6">
                    <h2 class="text-xl font-bold tracking-tight">Actualizar Cambio del Día</h2>
                    <p class="text-[0.8rem] mt-1" style="color:var(--text-muted)">
                        Fecha: {{ now()->translatedFormat('d \d\e F, Y') }}
                    </p>
                </div>
                
                <div class="erp-card-body p-8">
                    @if($errors->any())
                        <div class="flash-error mb-6">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('cotizaciones.store') }}" class="space-y-8">
                        @csrf
                        
                        {{-- Selección de Moneda --}}
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="moneda_destino" value="PYG" class="peer hidden" checked>
                                <div class="p-4 rounded-xl border-2 border-transparent bg-surface2 text-center transition-all peer-checked:border-primary peer-checked:bg-primary/5 hover:bg-surface3">
                                    <img src="https://flagcdn.com/w40/py.png" class="w-10 h-7 rounded mx-auto mb-2 object-cover" alt="PY">
                                    <span class="block text-xs font-bold uppercase tracking-widest">Guaraní</span>
                                </div>
                            </label>
                            
                            <label class="relative cursor-pointer">
                                <input type="radio" name="moneda_destino" value="BRL" class="peer hidden" {{ old('moneda_destino') == 'BRL' ? 'checked' : '' }}>
                                <div class="p-4 rounded-xl border-2 border-transparent bg-surface2 text-center transition-all peer-checked:border-primary peer-checked:bg-primary/5 hover:bg-surface3">
                                    <img src="https://flagcdn.com/w40/br.png" class="w-10 h-7 rounded mx-auto mb-2 object-cover" alt="BRL">
                                    <span class="block text-xs font-bold uppercase tracking-widest">Real</span>
                                </div>
                            </label>
                        </div>

                        {{-- Valor --}}
                        <div class="form-group text-center">
                            <label class="block text-[0.7rem] uppercase font-bold tracking-[0.1em] mb-3" style="color:var(--text-muted)">
                                TASA DE VENTA (vs USD)
                            </label>
                            <div class="relative max-w-[280px] mx-auto">
                                <input type="number" name="venta" value="{{ old('venta') }}" step="0.01" min="1" required
                                    class="w-full text-center text-3xl font-bold bg-transparent border-b-2 border-primary focus:outline-none focus:border-accent transition-colors pb-2"
                                    placeholder="0,00" autofocus>
                                <div class="mt-4 text-[0.7rem]" style="color:var(--text-muted)">
                                    * Se aplicará este valor para el cálculo principal.
                                </div>
                            </div>
                        </div>

                        <div class="pt-6">
                            <button type="submit" class="btn btn-primary w-full py-4 text-sm font-bold uppercase tracking-widest rounded-xl transition-all hover:scale-[1.02] active:scale-95 shadow-lg shadow-primary/20">
                                💾 Guardar Cotización
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .erp-card {
            border-radius: 24px !important;
            overflow: hidden;
            background: linear-gradient(145deg, var(--surface), var(--surface2));
        }
        .erp-card-header {
            background: transparent;
            border-bottom: 1px solid var(--border);
        }
    </style>
@endsection