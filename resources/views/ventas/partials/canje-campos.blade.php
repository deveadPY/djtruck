{{-- Datos del vehículo recibido como parte de pago. $idx puede ser un número o el literal JS "${idx}". --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-3 w-full">
    <p class="col-span-full text-xs" style="color:var(--text-muted)">
        Vehículo recibido: entra al stock como <strong>En preparación</strong>, con el monto del pago como valor de toma.
    </p>
    <div class="form-group">
        <label class="form-label">Marca *</label>
        <input type="text" name="pagos[{{ $idx }}][canje][marca]" maxlength="80" class="form-input" placeholder="Volvo">
    </div>
    <div class="form-group">
        <label class="form-label">Modelo *</label>
        <input type="text" name="pagos[{{ $idx }}][canje][modelo]" maxlength="80" class="form-input" placeholder="FH 460">
    </div>
    <div class="form-group">
        <label class="form-label">Año *</label>
        <input type="number" name="pagos[{{ $idx }}][canje][anio]" min="1950" max="{{ date('Y') + 1 }}" class="form-input">
    </div>
    <div class="form-group">
        <label class="form-label">Chasis *</label>
        <input type="text" name="pagos[{{ $idx }}][canje][numero_chasis]" maxlength="17" class="form-input" style="text-transform:uppercase">
    </div>
    <div class="form-group">
        <label class="form-label">Kilometraje</label>
        <input type="number" name="pagos[{{ $idx }}][canje][kilometraje]" min="0" class="form-input">
    </div>
</div>
