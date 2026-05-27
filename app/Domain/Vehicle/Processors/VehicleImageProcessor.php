<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Processors;

use App\Domain\Vehicle\Repositories\VehicleImageRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

/**
 * Procesa el ciclo de vida de las imágenes de un vehículo.
 *
 * Encapsula upload físico, persistencia y reglas de portada:
 * - Al crear vehículo, primera imagen es portada
 * - Al agregar más, conservan orden incremental sin alterar la portada existente
 * - Al eliminar la portada, se promueve automáticamente la siguiente imagen
 */
class VehicleImageProcessor
{
    private const UPLOAD_DIR = 'uploads/vehiculos';

    public function __construct(
        private readonly VehicleImageRepositoryInterface $imageRepository
    ) {}

    /**
     * Procesa imágenes al crear un vehículo nuevo.
     * Primera imagen siempre es portada.
     */
    public function process(int $vehicleId, array $imagenes): int
    {
        if (empty($imagenes)) {
            return 0;
        }

        $count = 0;
        foreach ($imagenes as $orden => $imagen) {
            if (!$imagen instanceof UploadedFile) {
                continue;
            }

            $this->storeImage($vehicleId, $imagen, $orden, $orden === 0);
            $count++;
        }

        return $count;
    }

    /**
     * Agrega imágenes adicionales a un vehículo existente.
     * No altera la portada actual; ordena después de las existentes.
     */
    public function appendMore(int $vehicleId, array $imagenes): int
    {
        if (empty($imagenes)) {
            return 0;
        }

        $nextOrder = $this->imageRepository->getNextOrderForVehicle($vehicleId);
        $hasExistingCover = $this->imageRepository->getFirstActiveForVehicle($vehicleId) !== null;

        $count = 0;
        foreach ($imagenes as $imagen) {
            if (!$imagen instanceof UploadedFile) {
                continue;
            }

            // Si no había imágenes previas, la primera nueva se vuelve portada
            $esPortada = !$hasExistingCover && $count === 0;

            $this->storeImage($vehicleId, $imagen, $nextOrder, $esPortada);
            $nextOrder++;
            $count++;
        }

        return $count;
    }

    /**
     * Elimina (soft) una imagen. Si era la portada, promueve la siguiente disponible.
     */
    public function remove(int $vehicleId, int $imageId): bool
    {
        $imagen = $this->imageRepository->findById($imageId);

        if (!$imagen || (int) $imagen->vehiculo_id !== $vehicleId) {
            return false;
        }

        $eraPortada = (bool) $imagen->es_portada;
        $this->imageRepository->softDelete($imageId, Auth::id());

        if ($eraPortada) {
            $this->promoteNextAsCover($vehicleId);
        }

        return true;
    }

    /**
     * Marca una imagen como portada y desmarca las demás del mismo vehículo.
     */
    public function setCover(int $vehicleId, int $imageId): bool
    {
        $imagen = $this->imageRepository->findById($imageId);

        if (!$imagen || (int) $imagen->vehiculo_id !== $vehicleId) {
            return false;
        }

        $this->imageRepository->clearCoversForVehicle($vehicleId);
        return $this->imageRepository->setAsCover($imageId);
    }

    private function storeImage(int $vehicleId, UploadedFile $imagen, int $orden, bool $esPortada): void
    {
        $nombre = $this->generateUniqueName($orden, $imagen);
        $imagen->move(public_path(self::UPLOAD_DIR), $nombre);

        $this->imageRepository->insert($vehicleId, [
            'ruta'            => self::UPLOAD_DIR . '/' . $nombre,
            'nombre_original' => $imagen->getClientOriginalName(),
            'orden'           => $orden,
            'es_portada'      => $esPortada,
        ]);
    }

    private function promoteNextAsCover(int $vehicleId): void
    {
        $siguiente = $this->imageRepository->getFirstActiveForVehicle($vehicleId);

        if ($siguiente !== null) {
            $this->imageRepository->setAsCover((int) $siguiente->id);
        }
    }

    private function generateUniqueName(int $orden, UploadedFile $imagen): string
    {
        return time() . '_' . $orden . '_' . $imagen->getClientOriginalName();
    }
}
