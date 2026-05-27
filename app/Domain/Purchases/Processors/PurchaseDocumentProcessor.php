<?php

declare(strict_types=1);

namespace App\Domain\Purchases\Processors;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Procesa los archivos adjuntos de una compra y los duplica para la factura asociada.
 */
class PurchaseDocumentProcessor
{
    private const COMPRA_DIR_PREFIX = 'uploads/documentos/compras/';
    private const FACTURA_DIR_PREFIX = 'uploads/documentos/facturas_proveedores/';

    public function process(int $compraId, int $facturaId, array $adjuntos): int
    {
        if (empty($adjuntos)) {
            return 0;
        }

        $compraDir = self::COMPRA_DIR_PREFIX . $compraId;
        $facturaDir = self::FACTURA_DIR_PREFIX . $facturaId;

        $this->ensureDirectoryExists($compraDir);
        $this->ensureDirectoryExists($facturaDir);

        $count = 0;
        foreach ($adjuntos as $archivo) {
            if (!$archivo instanceof UploadedFile) {
                continue;
            }

            $this->saveDocument($archivo, $compraId, $facturaId, $compraDir, $facturaDir);
            $count++;
        }

        return $count;
    }

    private function ensureDirectoryExists(string $dir): void
    {
        $fullPath = public_path($dir);
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0777, true);
        }
    }

    private function saveDocument(
        UploadedFile $archivo,
        int $compraId,
        int $facturaId,
        string $compraDir,
        string $facturaDir
    ): void {
        $nombreOriginal = $archivo->getClientOriginalName();
        $mimeType = $archivo->getClientMimeType();
        $tamano = $archivo->getSize();
        $nombre = time() . '_' . uniqid() . '_' . $nombreOriginal;

        $archivo->move(public_path($compraDir), $nombre);
        copy(public_path($compraDir . '/' . $nombre), public_path($facturaDir . '/' . $nombre));

        $now = now();
        $userId = Auth::id();

        DB::table('documentos')->insert([
            [
                'documentable_type' => 'compras',
                'documentable_id'   => $compraId,
                'ruta'              => $compraDir . '/' . $nombre,
                'nombre_original'   => $nombreOriginal,
                'mime_type'         => $mimeType,
                'tamano_bytes'      => $tamano,
                'created_by'        => $userId,
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            [
                'documentable_type' => 'facturas_proveedores',
                'documentable_id'   => $facturaId,
                'ruta'              => $facturaDir . '/' . $nombre,
                'nombre_original'   => $nombreOriginal,
                'mime_type'         => $mimeType,
                'tamano_bytes'      => $tamano,
                'created_by'        => $userId,
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
        ]);
    }
}
