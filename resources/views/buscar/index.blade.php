@extends('layouts.app')
@section('title', 'Buscar')
@section('page-title', 'Buscar')

@section('content')
    <form method="GET" action="{{ route('buscar') }}" class="mb-6 flex gap-2 max-w-2xl" role="search">
        <label for="buscar-q" class="sr-only">Buscar</label>
        <input id="buscar-q" type="search" name="q" value="{{ $q }}" minlength="2" autofocus
            placeholder="Chasis, marca, cliente, RUC, teléfono o N° de venta" class="form-input flex-1">
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>

    @if(mb_strlen($q) < 2)
        <p class="text-sm text-muted-foreground">Escribí al menos 2 caracteres.</p>
    @elseif(collect($resultados)->every(fn ($r) => $r->isEmpty()))
        <div class="erp-card p-8 text-center">
            <p class="text-foreground font-semibold">Sin resultados para “{{ $q }}”</p>
            <p class="text-sm text-muted-foreground mt-1">Probá con parte del chasis, el RUC o el número de venta.</p>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            @foreach([
                'vehiculos' => ['Vehículos', 'vehicles.show', 'vehicles.index'],
                'clientes'  => ['Clientes', 'clientes.show', 'clientes.index'],
                'ventas'    => ['Ventas', 'ventas.show', 'ventas.index'],
            ] as $tipo => [$titulo, $ruta, $rutaListado])
                @continue($resultados[$tipo]->isEmpty())
                <section class="erp-card" aria-labelledby="res-{{ $tipo }}">
                    <div class="erp-card-header"><h2 id="res-{{ $tipo }}">{{ $titulo }} ({{ $resultados[$tipo]->count() }})</h2></div>
                    <ul class="divide-y divide-border">
                        @foreach($resultados[$tipo] as $r)
                            <li>
                                <a href="{{ route($ruta, $r->id) }}" class="block px-5 py-3 hover:bg-surface-2 no-underline">
                                    @if($tipo === 'vehiculos')
                                        <span class="block text-sm font-semibold text-foreground">{{ $r->marca }} {{ $r->modelo }} {{ $r->anio }}</span>
                                        <span class="block text-xs text-muted-foreground">Chasis {{ $r->numero_chasis }} · {{ str_replace('_', ' ', $r->estado) }}</span>
                                    @elseif($tipo === 'clientes')
                                        <span class="block text-sm font-semibold text-foreground">{{ $r->razon_social }}</span>
                                        <span class="block text-xs text-muted-foreground">RUC {{ $r->ruc ?: '—' }} · {{ $r->telefono ?: 'sin teléfono' }}</span>
                                    @else
                                        <span class="block text-sm font-semibold text-foreground">{{ $r->numero_venta }}</span>
                                        <span class="block text-xs text-muted-foreground">{{ $r->cliente ?? 'Sin cliente' }} · {{ $r->fecha_venta ? \Carbon\Carbon::parse($r->fecha_venta)->format('d/m/Y') : '' }} · US$ {{ number_format((float) $r->precio_venta_usd, 2, ',', '.') }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    @if($resultados[$tipo]->count() >= 8)
                        <a href="{{ route($rutaListado, ['q' => $q] + ($tipo === 'vehiculos' ? ['estado' => 'TODOS'] : [])) }}" class="block px-5 py-3 text-sm text-primary border-t border-border no-underline">Ver todos en {{ $titulo }} →</a>
                    @endif
                </section>
            @endforeach
        </div>
    @endif
@endsection
