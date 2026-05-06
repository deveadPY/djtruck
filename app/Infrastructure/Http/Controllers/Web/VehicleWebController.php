<?php

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Http\Requests\StoreVehicleRequest;
use App\Infrastructure\Http\Requests\UpdateVehicleRequest;
use App\Domain\Vehicle\Repositories\VehicleRepositoryInterface;
use App\Application\Vehicle\CreateVehicleUseCase;
use App\Application\Vehicle\UpdateVehicleUseCase;

class VehicleWebController extends Controller
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicleRepository,
        private readonly CreateVehicleUseCase $createVehicleUseCase,
        private readonly UpdateVehicleUseCase $updateVehicleUseCase
    ) {}

    public function index(Request $request)
    {
        $q = $request->input('q');
        $vehiculos = $this->vehicleRepository->searchPaginated($q, 15);
        $vehiculos->appends(['q' => $q]); // Ensure pagination links keep the query

        return view('vehicles.index', compact('vehiculos', 'q'));
    }

    public function show($id)
    {
        $vehiculo = $this->vehicleRepository->findById((int) $id);
        if (!$vehiculo) abort(404);

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
        $imagenes = $request->hasFile('imagenes') ? $request->file('imagenes') : null;
        unset($data['imagenes']);

        $vehicle = $this->createVehicleUseCase->execute($data, $imagenes);

        return redirect()->route('vehicles.show', $vehicle->id)->with('success', 'Vehículo registrado correctamente.');
    }

    public function edit($id)
    {
        $vehiculo = $this->vehicleRepository->findById((int) $id);
        if (!$vehiculo) abort(404);

        $proveedores = DB::table('proveedores')->where('activo', true)->whereNull('deleted_at')->get();
        return view('vehicles.edit', compact('vehiculo', 'proveedores'));
    }

    public function update(UpdateVehicleRequest $request, $id)
    {
        $data = $request->validated();
        $imagenes = $request->hasFile('imagenes') ? $request->file('imagenes') : null;
        unset($data['imagenes']);

        $this->updateVehicleUseCase->execute((int) $id, $data, $imagenes);

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
        $this->vehicleRepository->delete((int) $id, Auth::id());
        return redirect()->route('vehicles.index')->with('success', 'Vehículo eliminado.');
    }
}
