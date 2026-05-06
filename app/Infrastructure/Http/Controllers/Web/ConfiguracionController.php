<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Web;

use App\Infrastructure\Persistence\Eloquent\Models\EmpresaConfigModel;
use App\Infrastructure\Settings\EmpresaSettings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $empresa = EmpresaSettings::get();
        return view('configuracion.empresa', compact('empresa'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nombre_empresa'    => 'required|string|max:200',
            'ruc'               => 'nullable|string|max:30',
            'telefono'          => 'nullable|string|max:30',
            'email'             => 'nullable|email|max:200',
            'direccion'         => 'nullable|string|max:300',
            'ciudad'            => 'nullable|string|max:100',
            'pais'              => 'nullable|string|max:100',
            'sitio_web'         => 'nullable|url|max:200',
            'moneda_base'       => 'required|in:USD,PYG,BRL',
            'prefijo_venta'     => 'required|string|max:5',
            'prefijo_factura'   => 'required|string|max:5',
            'logo'              => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $empresa = EmpresaConfigModel::firstOrCreate(['id' => 1]);

        // Handle logo upload — save directly in public/uploads/logos/ (no symlink needed)
        if ($request->hasFile('logo')) {
            // Delete old logo file
            if ($empresa->logo_path) {
                $oldFile = public_path($empresa->logo_path);
                if (File::exists($oldFile)) {
                    File::delete($oldFile);
                }
            }

            $file = $request->file('logo');
            $nombre = 'logo_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/logos'), $nombre);
            $validated['logo_path'] = 'uploads/logos/' . $nombre;
        }

        unset($validated['logo']);
        $empresa->fill($validated)->save();

        EmpresaSettings::forget();

        return redirect()->route('config.index')
            ->with('success', 'Configuración guardada correctamente.');
    }

    public function destroyLogo()
    {
        $empresa = EmpresaConfigModel::first();
        if ($empresa && $empresa->logo_path) {
            $file = public_path($empresa->logo_path);
            if (File::exists($file)) {
                File::delete($file);
            }
            $empresa->update(['logo_path' => null]);
            EmpresaSettings::forget();
        }

        return redirect()->route('config.index')
            ->with('success', 'Logo eliminado.');
    }
}
