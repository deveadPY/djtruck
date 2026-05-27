<?php

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DocumentoWebController extends Controller
{
    private const ALLOWED_TYPES = [
        'clientes',
        'facturas_proveedores',
        'vehiculos',
        'proveedores',
        'compras',
    ];

    public function upload(Request $request)
    {
        $request->validate([
            'documentable_type' => 'required|string|in:' . implode(',', self::ALLOWED_TYPES),
            'documentable_id'   => 'required|integer|min:1',
            'descripcion'       => 'nullable|string|max:255',
            'tipo'              => 'nullable|string|max:50',
            'archivos'          => 'required|array|min:1|max:10',
            'archivos.*'        => 'file|max:20480',
        ]);

        $type = $request->input('documentable_type');
        $id   = $request->input('documentable_id');
        $desc = $request->input('descripcion');
        $tipo = $request->input('tipo');

        // VALIDACIÓN DE SEGURIDAD (IDOR): Verificar que el usuario tiene permiso para ver/editar la entidad
        $this->authorizeAccessToEntity($type, (int)$id);

        $uploadDir = 'uploads/documentos/' . $type . '/' . $id;

        foreach ($request->file('archivos') as $archivo) {
            $nombreOriginal = $archivo->getClientOriginalName();
            $mimeType = $archivo->getClientMimeType();
            $tamano = $archivo->getSize();

            $nombre = time() . '_' . uniqid() . '_' . $nombreOriginal;
            $archivo->move(public_path($uploadDir), $nombre);

            DB::table('documentos')->insert([
                'documentable_type' => $type,
                'documentable_id'   => $id,
                'ruta'              => $uploadDir . '/' . $nombre,
                'nombre_original'   => $nombreOriginal,
                'mime_type'         => $mimeType,
                'tamano_bytes'      => $tamano,
                'descripcion'       => $desc,
                'tipo'              => $tipo ?: null,
                'created_by'        => Auth::id(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        return back()->with('success', 'Documento(s) cargado(s) correctamente.');
    }

    public function download($id)
    {
        $doc = DB::table('documentos')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
        
        // VALIDACIÓN DE SEGURIDAD (IDOR): Verificar acceso a la entidad dueña del documento
        $this->authorizeAccessToEntity($doc->documentable_type, (int)$doc->documentable_id);

        $path = public_path($doc->ruta);

        if (!file_exists($path)) {
            abort(404, 'Archivo no encontrado en disco.');
        }

        return response()->download($path, $doc->nombre_original);
    }

    public function destroy($id)
    {
        $doc = DB::table('documentos')->where('id', $id)->whereNull('deleted_at')->firstOrFail();

        // VALIDACIÓN DE SEGURIDAD (IDOR): Verificar permiso de edición en la entidad
        $this->authorizeAccessToEntity($doc->documentable_type, (int)$doc->documentable_id);

        DB::table('documentos')->where('id', $id)->update([
            'deleted_at' => now(),
        ]);

        return back()->with('success', 'Documento eliminado.');
    }

    /**
     * Valida que el usuario tenga permisos suficientes sobre la entidad documentable.
     */
    private function authorizeAccessToEntity(string $type, int $id): void
    {
        $user = Auth::user();

        // Mapeo de tipos a permisos específicos del sistema (basado en routes/web.php)
        $permissionMap = [
            'clientes'             => 'clientes.ver',
            'vehiculos'            => 'vehiculos.ver',
            'proveedores'          => 'proveedores.ver',
            'facturas_proveedores' => 'repuestos.ver',
            'compras'              => 'repuestos.ver',
            'ventas'               => 'ventas.ver',
        ];

        $permission = $permissionMap[$type] ?? null;

        if ($permission && !$user->can($permission)) {
            abort(403, "No tienes permiso para acceder a los documentos de esta entidad ($type).");
        }

        // Verificar que la entidad existe
        $exists = DB::table($type)->where('id', $id)->whereNull('deleted_at')->exists();
        if (!$exists) {
            abort(404, "La entidad destino no existe o ha sido eliminada.");
        }
    }
}
