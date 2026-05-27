{{--
    Partial: select de proveedor con botón "+" para crear inline.
    Variables esperadas:
      - $proveedores  Collection (id, razon_social)
      - $selected     mixed (id o null)
--}}
<div style="display:flex; gap:.5rem; align-items:stretch;">
    <select name="proveedor_id" id="proveedor_id" class="form-input" style="flex:1;">
        <option value="">-- Sin proveedor --</option>
        @foreach($proveedores as $p)
            <option value="{{ $p->id }}" @selected($selected == $p->id)>{{ $p->razon_social }}</option>
        @endforeach
    </select>
    <button type="button"
            id="btnOpenProveedorModal"
            title="Crear nuevo proveedor"
            style="padding:0 .9rem; background:var(--primary); color:#fff; border:none; border-radius:8px; font-weight:700; font-size:1.1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; min-width:42px;">
        +
    </button>
</div>
<small style="display:block; margin-top:.35rem; color:var(--text-muted); font-size:.78rem;">
    ¿No está? Tocá <strong>+</strong> para crearlo sin salir de esta pantalla.
</small>
