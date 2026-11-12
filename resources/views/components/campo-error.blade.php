@props(['name'])
{{-- Error de validación junto a su campo (el input lo referencia con aria-describedby="{name}-error"). --}}
@error($name)
    <p id="{{ $name }}-error" class="text-xs mt-1" style="color: var(--danger);">{{ $message }}</p>
@enderror
