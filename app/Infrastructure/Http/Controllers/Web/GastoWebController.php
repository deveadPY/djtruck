<?php

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use App\Infrastructure\Http\Requests\StoreVehicleExpenseRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GastoWebController extends Controller
{
    public function create($vehiculoId)
    {
        $vehiculo = DB::table('vehiculos')->where('id', $vehiculoId)->whereNull('deleted_at')->firstOrFail();
        $repuestos = DB::table('stock_repuestos')->where('activo', true)->whereNull('deleted_at')->get();
        return view('gastos.create', compact('vehiculo', 'repuestos'));
    }

    public function store(StoreVehicleExpenseRequest $request, $vehiculoId)
    {
        DB::table('vehiculos')->where('id', $vehiculoId)->whereNull('deleted_at')->firstOrFail();

        $data = $request->gasto();
        $data['vehiculo_id'] = $vehiculoId;
        $data['created_by'] = Auth::id();
        $data['aplicado_al_costo'] = false;

        DB::transaction(function () use ($request, $data, $vehiculoId) {
            $gastoId = DB::table('gastos_vehiculo')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);

            if ($archivo = $request->file('comprobante')) {
                // Mismo almacenamiento que el módulo de documentos (tabla polimórfica `documentos`).
                $dir = 'uploads/documentos/gastos_vehiculo/' . $gastoId;
                $nombre = Str::random(40) . '.' . $archivo->guessExtension();
                $nombreOriginal = $archivo->getClientOriginalName();
                $mime = $archivo->getMimeType();
                $tamano = $archivo->getSize();
                $archivo->move(public_path($dir), $nombre);

                DB::table('documentos')->insert([
                    'documentable_type' => 'gastos_vehiculo',
                    'documentable_id'   => $gastoId,
                    'ruta'              => $dir . '/' . $nombre,
                    'nombre_original'   => $nombreOriginal,
                    'mime_type'         => $mime,
                    'tamano_bytes'      => $tamano,
                    'tipo'              => 'COMPROBANTE',
                    'created_by'        => Auth::id(),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            $this->recalcularTotal($vehiculoId);
        });

        if ($request->boolean('otro')) {
            // Se conservan moneda y categoría (ya validadas) para cargar el siguiente gasto más rápido.
            return redirect()->route('gastos.create', $vehiculoId)->with([
                'success'         => 'Gasto registrado. Puedes cargar el siguiente.',
                'gasto_moneda'    => $data['moneda'],
                'gasto_categoria' => $data['categoria'],
            ]);
        }

        return redirect()->route('vehicles.show', $vehiculoId)->with('success', 'Gasto registrado y valor libro actualizado.');
    }

    public function destroy($vehiculoId, $gastoId)
    {
        DB::table('gastos_vehiculo')->where('id', $gastoId)->where('vehiculo_id', $vehiculoId)->update([
            'deleted_at' => now(),
            'deleted_by' => Auth::id(),
        ]);

        $this->recalcularTotal($vehiculoId);

        return redirect()->route('vehicles.show', $vehiculoId)->with('success', 'Gasto eliminado.');
    }

    private function recalcularTotal($vehiculoId): void
    {
        $total = DB::table('gastos_vehiculo')
            ->where('vehiculo_id', $vehiculoId)
            ->whereNull('deleted_at')
            ->sum('monto_usd');
        DB::table('vehiculos')->where('id', $vehiculoId)->update(['total_gastos_usd' => $total, 'updated_at' => now()]);
    }
}
