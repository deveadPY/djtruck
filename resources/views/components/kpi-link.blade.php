{{-- Tarjeta KPI que lleva a su módulo. Sin permiso ($can) o sin $href queda como tarjeta normal. --}}
@props(['href' => null, 'can' => null])
@if($href && (!$can || auth()->user()?->can($can)))
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'block no-underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary', 'style' => 'color:inherit;']) }}>{{ $slot }}</a>
@else
    <div {{ $attributes->merge(['class' => '!cursor-default']) }}>{{ $slot }}</div>
@endif
