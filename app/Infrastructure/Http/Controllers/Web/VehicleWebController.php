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

    private const ESTADOS_FILTRO = [
        'STOCK'          => 'En stock (no vendidos)',
        'DISPONIBLE'     => 'Disponible',
        'EN_PREPARACION' => 'En preparación',
        'RESERVADO'      => 'Reservado',
        'EN_TRANSITO'    => 'En tránsito',
        'EN_ADUANA'      => 'En aduana',
        'TOMA'           => 'Toma',
        'VENDIDO'        => 'Vendido',
        'BAJA'           => 'Baja',
    ];

    public function index(Request $request)
    {
        $q = $request->input('q');
        // Por defecto se lista el stock; "TODOS" quita el filtro.
        $estado = $request->input('estado', 'STOCK');
        if ($estado !== 'TODOS' && !array_key_exists($estado, self::ESTADOS_FILTRO)) {
            $estado = 'STOCK';
        }
        $vehiculos = $this->vehicleRepository->searchPaginated($q, 15, $estado === 'TODOS' ? null : $estado);
        $vehiculos->appends(['q' => $q, 'estado' => $estado]); // Ensure pagination links keep the query
        $estados = self::ESTADOS_FILTRO;

        return view('vehicles.index', compact('vehiculos', 'q', 'estado', 'estados'));
    }

    public function show($id)
    {
        $vehiculo = $this->vehicleRepository->findById((int) $id);
        if (!$vehiculo) abort(404);

        $gastos = DB::table('gastos_vehiculo as g')
            ->leftJoin('users as u', 'g.created_by', '=', 'u.id')
            ->where('g.vehiculo_id', $id)
            ->whereNull('g.deleted_at')
            ->orderByDesc('g.fecha_gasto')
            ->orderByDesc('g.id')
            ->select('g.*', 'u.name as registrado_por')
            ->selectSub(
                DB::table('documentos')->select('id')
                    ->where('documentable_type', 'gastos_vehiculo')
                    ->whereColumn('documentable_id', 'g.id')
                    ->whereNull('deleted_at')
                    ->latest('id')->limit(1),
                'comprobante_id'
            )
            ->get();
        $proveedor = $vehiculo->proveedor_id ? DB::table('proveedores')->where('id', $vehiculo->proveedor_id)->first() : null;
        $imagenes = DB::table('vehiculo_imagenes')->where('vehiculo_id', $id)->whereNull('deleted_at')->orderBy('orden')->get();

        $documentos = DB::table('documentos')
            ->where('documentable_type', 'vehiculos')
            ->where('documentable_id', $id)
            ->whereNull('deleted_at')
            ->latest()
            ->get();

        $estados = self::ESTADOS_FILTRO;

        return view('vehicles.show', compact('vehiculo', 'gastos', 'proveedor', 'imagenes', 'documentos', 'estados'));
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
        $estados     = self::ESTADOS_FILTRO;
        // VENDIDO lo fija la venta: no se elige desde este formulario.
        $estadosEditables = array_diff_key($estados, array_flip(['STOCK', 'VENDIDO']));

        return view('vehicles.edit', compact('vehiculo', 'proveedores', 'imagenes', 'estados', 'estadosEditables'));
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
