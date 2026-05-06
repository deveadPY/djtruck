<?php

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Http\Requests\StoreVehicleRequest;
use App\Infrastructure\Http\Requests\UpdateVehicleRequest;

class VehicleWebController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');

        $query = DB::table('vehiculos')
            ->leftJoin('ventas', function ($join) {
                $join->on('vehiculos.id', '=', 'ventas.vehiculo_id')
                    ->whereNull('ventas.deleted_at');
            })
            ->select(
                'vehiculos.*',
                'ventas.precio_venta_usd as venta_precio_usd',
                'ventas.precio_venta_moneda as venta_precio_moneda',
                'ventas.moneda_venta as venta_moneda'
            )
            ->whereNull('vehiculos.deleted_at');

        if ($q) {
            $query->where(function ($query) use ($q) {
                $query->where('vehiculos.marca', 'like', "%{$q}%")
                      ->orWhere('vehiculos.modelo', 'like', "%{$q}%")
                      ->orWhere('vehiculos.numero_chasis', 'like', "%{$q}%")
                      ->orWhere('vehiculos.numero_motor', 'like', "%{$q}%");
            });
        }

        $vehiculos = $query->latest('vehiculos.created_at')
            ->paginate(15)
            ->withQueryString();

        return view('vehicles.index', compact('vehiculos', 'q'));
    }

    public function show($id)
    {
        $vehiculo = DB::table('vehiculos')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
        $gastos = DB::table('gastos_vehiculo')->where('vehiculo_id', $id)->whereNull('deleted_at')->latest()->get();
        $proveedor = $vehiculo->proveedor_id ? DB::table('proveedores')->where('id', $vehiculo->proveedor_id)->first() : null;
        $imagenes = DB::table('vehiculo_imagenes')->where('vehiculo_id', $id)->whereNull('deleted_at')->orderBy('orden')->get();

        $documentos = DB::table('documentos')
            ->where('documentable_type', 'vehiculos')
            ->where('documentable_id', $id)
            ->whereNull('deleted_at')
            ->latest()
            ->get();

        return view('vehicles.show', compact('vehiculo', 'gastos', 'proveedor', 'imagenes', 'documentos'));
    }

    public function create()
    {
        $proveedores = DB::table('proveedores')->where('activo', true)->whereNull('deleted_at')->get();
        return view('vehicles.create', compact('proveedores'));
    }

    public function store(StoreVehicleRequest $request)
    {
        $data = $request->validated();

        $data['created_by'] = Auth::id();
        $data['kilometraje'] = $data['kilometraje'] ?? 0;
        $data['total_gastos_usd'] = 0;

        // Remove imagenes from data before inserting (not a DB column)
        unset($data['imagenes']);

        $vehiculoId = DB::table('vehiculos')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);

        // Handle image uploads
        if ($request->hasFile('imagenes')) {
            $orden = 0;
            foreach ($request->file('imagenes') as $imagen) {
                $nombre = time() . '_' . $orden . '_' . $imagen->getClientOriginalName();
                $imagen->move(public_path('uploads/vehiculos'), $nombre);

                DB::table('vehiculo_imagenes')->insert([
                    'vehiculo_id' => $vehiculoId,
                    'ruta' => 'uploads/vehiculos/' . $nombre,
                    'nombre_original' => $imagen->getClientOriginalName(),
                    'orden' => $orden,
                    'es_portada' => $orden === 0,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $orden++;
            }
        }

        return redirect()->route('vehicles.show', $vehiculoId)->with('success', 'Vehículo registrado correctamente.');
    }

    public function edit($id)
    {
        $vehiculo = DB::table('vehiculos')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
        $proveedores = DB::table('proveedores')->where('activo', true)->whereNull('deleted_at')->get();
        return view('vehicles.edit', compact('vehiculo', 'proveedores'));
    }

    public function update(UpdateVehicleRequest $request, $id)
    {
        $data = $request->validated();

        $data['updated_by'] = Auth::id();
        $data['updated_at'] = now();

        // Remove imagenes from data before updating (not a DB column)
        unset($data['imagenes']);

        DB::table('vehiculos')->where('id', $id)->update($data);

        // Handle new image uploads
        if ($request->hasFile('imagenes')) {
            $ultimoOrden = DB::table('vehiculo_imagenes')
                ->where('vehiculo_id', $id)
                ->whereNull('deleted_at')
                ->max('orden') ?? -1;

            foreach ($request->file('imagenes') as $imagen) {
                $ultimoOrden++;
                $nombre = time() . '_' . $ultimoOrden . '_' . $imagen->getClientOriginalName();
                $imagen->move(public_path('uploads/vehiculos'), $nombre);

                DB::table('vehiculo_imagenes')->insert([
                    'vehiculo_id'     => $id,
                    'ruta'            => 'uploads/vehiculos/' . $nombre,
                    'nombre_original' => $imagen->getClientOriginalName(),
                    'orden'           => $ultimoOrden,
                    'es_portada'      => false,
                    'created_by'      => Auth::id(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }

        return redirect()->route('vehicles.show', $id)->with('success', 'Vehículo actualizado.');
    }

    public function destroyImagen($vehiculoId, $imagenId)
    {
        $imagen = DB::table('vehiculo_imagenes')
            ->where('id', $imagenId)
            ->where('vehiculo_id', $vehiculoId)
            ->whereNull('deleted_at')
            ->first();

        if (!$imagen) {
            abort(404);
        }

        // Soft delete
        DB::table('vehiculo_imagenes')->where('id', $imagenId)->update([
            'deleted_at' => now(),
            'deleted_by' => Auth::id(),
        ]);

        // If the cover was deleted, promote next image to cover
        if ($imagen->es_portada) {
            $siguiente = DB::table('vehiculo_imagenes')
                ->where('vehiculo_id', $vehiculoId)
                ->whereNull('deleted_at')
                ->orderBy('orden')
                ->first();

            if ($siguiente) {
                DB::table('vehiculo_imagenes')->where('id', $siguiente->id)->update(['es_portada' => true]);
            }
        }

        return back()->with('success', 'Imagen eliminada.');
    }

    public function setPortada($vehiculoId, $imagenId)
    {
        // Remove cover from all images of this vehicle
        DB::table('vehiculo_imagenes')
            ->where('vehiculo_id', $vehiculoId)
            ->update(['es_portada' => false]);

        // Set new cover
        DB::table('vehiculo_imagenes')
            ->where('id', $imagenId)
            ->where('vehiculo_id', $vehiculoId)
            ->update(['es_portada' => true, 'updated_at' => now()]);

        return back()->with('success', 'Imagen de portada actualizada.');
    }

    public function destroy($id)
    {
        DB::table('vehiculos')->where('id', $id)->update([
            'deleted_at' => now(),
            'deleted_by' => Auth::id(),
        ]);
        return redirect()->route('vehicles.index')->with('success', 'Vehículo eliminado.');
    }
}
