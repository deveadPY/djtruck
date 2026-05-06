<?php

declare(strict_types=1);

namespace App\Application\Vehicle;

use App\Domain\Vehicle\Repositories\VehicleRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreateVehicleUseCase
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicleRepository
    ) {}

    public function execute(array $data, ?array $imagenes = null): VehicleModel
    {
        $data['created_by'] = Auth::id();
        $data['kilometraje'] = $data['kilometraje'] ?? 0;
        $data['total_gastos_usd'] = 0;

        return DB::transaction(function () use ($data, $imagenes) {
            $vehicle = $this->vehicleRepository->create($data);

            if ($imagenes && count($imagenes) > 0) {
                $orden = 0;
                foreach ($imagenes as $imagen) {
                    $nombre = time() . '_' . $orden . '_' . $imagen->getClientOriginalName();
                    $imagen->move(public_path('uploads/vehiculos'), $nombre);

                    DB::table('vehiculo_imagenes')->insert([
                        'vehiculo_id' => $vehicle->id,
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

            return $vehicle;
        });
    }
}
