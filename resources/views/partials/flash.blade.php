{{-- Mensajes de sesión de todo el sistema (una sola vez, en el layout). Una vista con su propio aviso define @section('flash-propio'). --}}
@foreach(['success' => 'flash-success', 'info' => 'flash-info', 'warning' => 'flash-warning', 'error' => 'flash-error'] as $tipo => $clase)
    @if(session($tipo))
        <div class="{{ $clase }}" role="{{ $tipo === 'error' ? 'alert' : 'status' }}">{{ session($tipo) }}</div>
    @endif
@endforeach
