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
use App\Application\Vehicle\CreateVehicleDTO;
use App\Application\Vehicle\UpdateVehicleUseCase;
use App\Domain\Vehicle\Processors\VehicleImageProcessor;
use App\Domain\Vehicle\Repositories\VehicleImageRepositoryInterface;

class VehicleWebController extends Controller
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicleRepository,
        private readonly CreateVehicleUseCase $createVehicleUseCase,
        private readonly UpdateVehicleUseCase $updateVehicleUseCase,
        private readonly VehicleImageProcessor $imageProcessor,
        private readonly VehicleImageRepositoryInterface $imageRepository
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
        $vehicle = $this->createVehicleUseCase->execute(CreateVehicleDTO::fromRequest($request));

        return redirect()->route('vehicles.show', $vehicle->id)->with('success', 'Vehículo registrado correctamente.');
    }

    public function edit($id)
    {
        $vehiculo = $this->vehicleRepository->findById((int) $id);
        if (!$vehiculo) abort(404);

        $proveedores = DB::table('proveedores')->where('activo', true)->whereNull('deleted_at')->get();
        $imagenes    = $this->imageRepository->getByVehicle((int) $id);

        return view('vehicles.edit', compact('vehiculo', 'proveedores', 'imagenes'));
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
        $eliminado = $this->imageProcessor->remove((int) $vehiculoId, (int) $imagenId);

        if (!$eliminado) {
            return back()->withErrors(['error' => 'Imagen no encontrada o ya eliminada.']);
        }

        return back()->with('success', 'Imagen eliminada.');
    }

    public function setPortada($vehiculoId, $imagenId)
    {
        $actualizado = $this->imageProcessor->setCover((int) $vehiculoId, (int) $imagenId);

        if (!$actualizado) {
            return back()->withErrors(['error' => 'No se pudo actualizar la portada.']);
        }

        return back()->with('success', 'Imagen de portada actualizada.');
    }

    public function destroy($id)
    {
        $this->vehicleRepository->delete((int) $id, Auth::id());
        return redirect()->route('vehicles.index')->with('success', 'Vehículo eliminado.');
    }
}
