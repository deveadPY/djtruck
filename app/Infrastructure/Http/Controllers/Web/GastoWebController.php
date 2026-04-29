<?php

namespace App\Infrastructure\Http\Controllers\Web;

use App\Infrastructure\Http\Requests\StoreGastoRequest;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GastoWebController extends Controller
{
    public function create($vehiculoId)
    {
        $vehiculo = DB::table('vehiculos')->where('id', $vehiculoId)->whereNull('deleted_at')->firstOrFail();
        $repuestos = DB::table('stock_repuestos')->where('activo', true)->whereNull('deleted_at')->get();
        return view('gastos.create', compact('vehiculo', 'repuestos'));
    }

    public function store(StoreGastoRequest $request, $vehiculoId)
    {
        $data = $request->validated();

        $data['vehiculo_id'] = $vehiculoId;
        $data['created_by'] = Auth::id();
        $data['aplicado_al_costo'] = false;

        DB::table('gastos_vehiculo')->insert($data + ['created_at' => now(), 'updated_at' => now()]);

        // Actualizar total_gastos_usd en el vehículo
        $total = DB::table('gastos_vehiculo')
            ->where('vehiculo_id', $vehiculoId)
            ->whereNull('deleted_at')
            ->sum('monto_usd');
        DB::table('vehiculos')->where('id', $vehiculoId)->update([
            'total_gastos_usd' => $total,
            'updated_at' => now(),
        ]);

        return redirect()->route('vehicles.show', $vehiculoId)->with('success', 'Gasto registrado y valor libro actualizado.');
    }

    public function destroy($vehiculoId, $gastoId)
    {
        DB::table('gastos_vehiculo')->where('id', $gastoId)->update([
            'deleted_at' => now(),
            'deleted_by' => Auth::id(),
        ]);

        // Recalculate
        $total = DB::table('gastos_vehiculo')
            ->where('vehiculo_id', $vehiculoId)
            ->whereNull('deleted_at')
            ->sum('monto_usd');
        DB::table('vehiculos')->where('id', $vehiculoId)->update(['total_gastos_usd' => $total, 'updated_at' => now()]);

        return redirect()->route('vehicles.show', $vehiculoId)->with('success', 'Gasto eliminado.');
    }
}
