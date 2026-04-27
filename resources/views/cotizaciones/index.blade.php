@extends('layouts.app')
@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones y Monedas')

@section('content')
    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    {{-- ── Tarjetas de Cotización Actual ────────────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 mt-4">
        {{-- PYG Card --}}
        <div class="erp-card overflow-hidden transition-all hover:scale-[1.01]" style="border-left: 4px solid var(--accent);">
            <div class="p-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-surface2 flex items-center justify-center shadow-inner overflow-hidden">
                        <img src="https://flagcdn.com/w80/py.png" class="w-full h-full object-cover" alt="PY">
                    </div>
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-widest" style="color:var(--text-muted)">Guaraní (PYG)</h3>
                        <p class="text-[0.65rem] italic" style="color:var(--text-muted)">Última actualización: {{ $latestPyg ? \Carbon\Carbon::parse($latestPyg->created_at)->format('d/m/Y H:i') : 'N/A' }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-black text-accent tracking-tighter">
                        {{ $latestPyg ? number_format($latestPyg->venta, 0, ',', '.') : '0' }}
                    </div>
                    <div class="text-[0.6rem] font-bold opacity-50 uppercase">Tasa de Venta</div>
                </div>
            </div>
        </div>

        {{-- BRL Card --}}
        <div class="erp-card overflow-hidden transition-all hover:scale-[1.01]" style="border-left: 4px solid #FCD34D;">
            <div class="p-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-surface2 flex items-center justify-center shadow-inner overflow-hidden">
                        <img src="https://flagcdn.com/w80/br.png" class="w-full h-full object-cover" alt="BRL">
                    </div>
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-widest" style="color:var(--text-muted)">Real (BRL)</h3>
                        <p class="text-[0.65rem] italic" style="color:var(--text-muted)">Última actualización: {{ $latestBrl ? \Carbon\Carbon::parse($latestBrl->created_at)->format('d/m/Y H:i') : 'N/A' }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-black tracking-tighter" style="color:#FCD34D">
                        {{ $latestBrl ? number_format($latestBrl->venta, 2, ',', '.') : '0,00' }}
                    </div>
                    <div class="text-[0.6rem] font-bold opacity-50 uppercase">Tasa de Venta</div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 class="text-sm font-bold uppercase tracking-widest" style="color:var(--text-muted)">Historial de Cambios</h2>
        <a href="{{ route('cotizaciones.create') }}" class="btn btn-primary shadow-lg shadow-primary/20">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Actualizar Cotización
        </a>
    </div>

    <div class="erp-card">
        <div class="erp-card-body" style="padding:0">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th class="pl-6">Fecha y Hora</th>
                        <th>Moneda</th>
                        <th>Venta (ERP)</th>
                        <th>Compra</th>
                        <th class="pr-6">Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cotizaciones as $c)
                        <tr>
                            <td class="pl-6">
                                <div class="font-bold">{{ \Carbon\Carbon::parse($c->fecha)->format('d/m/Y') }}</div>
                                <div class="text-[0.65rem] opacity-60">{{ \Carbon\Carbon::parse($c->created_at)->format('H:i') }} hs</div>
                            </td>
                            <td>
                                @if($c->moneda_destino === 'PYG')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[0.65rem] font-bold bg-accent/10 text-accent">
                                        <span class="w-1.5 h-1.5 rounded-full bg-accent"></span> ₲ - Guaraní
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[0.65rem] font-bold bg-yellow-500/10 text-yellow-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> R$ - Real
                                    </span>
                                @endif
                            </td>
                            <td><strong class="text-base" style="color:var(--white)">{{ number_format($c->venta, 2, ',', '.') }}</strong></td>
                            <td class="opacity-40">{{ number_format($c->compra, 2, ',', '.') }}</td>
                            <td class="pr-6 text-[0.7rem] italic opacity-60">
                                {{ DB::table('users')->where('id', $c->created_by)->value('name') ?? 'Sistema' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;color:var(--text-muted);padding:4rem">Aún no hay cotizaciones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection