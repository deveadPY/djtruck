<?php

declare(strict_types=1);

namespace App\Application\Vehicle;

use App\Domain\Vehicle\Repositories\VehicleRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UpdateVehicleUseCase
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicleRepository
    ) {}

    public function execute(int $id, array $data, ?array $imagenes = null): bool
    {
        $data['updated_by'] = Auth::id();
        $data['updated_at'] = now();

        return DB::transaction(function () use ($id, $data, $imagenes) {
            $updated = $this->vehicleRepository->update($id, $data);

            if ($imagenes && count($imagenes) > 0) {
                $ultimoOrden = DB::table('vehiculo_imagenes')
                    ->where('vehiculo_id', $id)
                    ->whereNull('deleted_at')
                    ->max('orden') ?? -1;

                foreach ($imagenes as $imagen) {
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

            return $updated;
        });
    }
}
