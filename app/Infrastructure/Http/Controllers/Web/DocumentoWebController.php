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
                'tamaño_bytes'      => $tamano,
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
        $path = public_path($doc->ruta);

        if (!file_exists($path)) {
            abort(404, 'Archivo no encontrado en disco.');
        }

        return response()->download($path, $doc->nombre_original);
    }

    public function destroy($id)
    {
        DB::table('documentos')->where('id', $id)->update([
            'deleted_at' => now(),
        ]);

        return back()->with('success', 'Documento eliminado.');
    }
}
