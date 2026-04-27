<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Web;

use App\Infrastructure\SIFEN\ElectronicInvoicingService;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SifenWebController extends Controller
{
    public function __construct(
        private readonly ElectronicInvoicingService $sifen
    ) {}

    public function index(Request $request)
    {
        $filtro = $request->get('filtro', 'pendientes'); // pendientes | emitidas | errores

        // ── Estado del servicio ───────────────────────────────────────────
        $statusSifen = [
            'ambiente'        => config('sifen.ambiente'),
            'ruc_emisor'      => config('sifen.ruc_emisor') ?: 'No configurado',
            'numero_timbrado' => config('sifen.numero_timbrado') ?: 'No configurado',
            'cert_path'       => config('sifen.cert_path'),
            'cert_ok'         => file_exists(config('sifen.cert_path', '')),
        ];

        // ── Totales ───────────────────────────────────────────────────────
        $totalPendientes = SaleModel::where('tiene_factura_electronica', false)
            ->where('estado', 'COMPLETADO')
            ->whereNull('deleted_at')
            ->count();

        $totalEmitidas = SaleModel::where('tiene_factura_electronica', true)
            ->whereNull('deleted_at')
            ->count();

        $totalConError = SaleModel::where(function($q) {
                $q->whereNotNull('sifen_error')
                  ->orWhere('estado_sifen', 'RECHAZADO');
            })
            ->where('tiene_factura_electronica', false)
            ->whereNull('deleted_at')
            ->count();

        // ── Listado según filtro ──────────────────────────────────────────
        $query = SaleModel::with(['vehiculo', 'cliente'])
            ->whereNull('deleted_at')
            ->orderByDesc('fecha_venta');

        $ventas = match($filtro) {
            'emitidas'  => $query->where('tiene_factura_electronica', true)->paginate(20),
            'errores'   => $query->where(function($q) {
                                $q->whereNotNull('sifen_error')
                                  ->orWhere('estado_sifen', 'RECHAZADO');
                            })->where('tiene_factura_electronica', false)->paginate(20),
            default     => $query->where('tiene_factura_electronica', false)->where('estado', 'COMPLETADO')->paginate(20),
        };

        return view('sifen.index', compact(
            'statusSifen',
            'filtro',
            'ventas',
            'totalPendientes',
            'totalEmitidas',
            'totalConError',
        ));
    }

    public function emitir(Request $request, int $saleId)
    {
        $venta = SaleModel::findOrFail($saleId);
        $tipo  = $request->get('tipo', '01'); // 01: Factura, 04: Nota de Crédito, etc.

        try {
            $result = $this->sifen->emitirFactura($venta, $tipo);
            
            if ($result['estado'] === 'RECHAZADO') {
                return redirect()->route('sifen.index', ['filtro' => 'errores'])
                    ->with('error', "Documento #{$venta->numero_venta} RECHAZADO por SIFEN. Verifique los datos del receptor.");
            }

            return redirect()->route('sifen.index', ['filtro' => 'emitidas'])
                ->with('success', "Factura electrónica #{$venta->numero_venta} APROBADA correctamente.");
        } catch (\Throwable $e) {
            $venta->update([
                'sifen_error' => $e->getMessage(),
                'estado_sifen' => 'ERROR'
            ]);
            return redirect()->route('sifen.index', ['filtro' => 'errores'])
                ->with('error', "Error crítico al emitir #{$venta->numero_venta}: {$e->getMessage()}");
        }
    }

    public function downloadKude(int $id)
    {
        $venta = SaleModel::findOrFail($id);

        // Si ya fue aprobada pero por alguna razón no tiene el archivo físico, intentamos regenerarlo
        if ($venta->estado_sifen === 'APROBADO' && (!$venta->sifen_kude_path || !Storage::disk('public')->exists($venta->sifen_kude_path))) {
            try {
                $this->sifen->emitirFactura($venta, $venta->tipo_comprobante_sifen ?: '01');
                $venta->refresh();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Error regenerando KuDE para Sale #{$id}: " . $e->getMessage());
            }
        }

        if (!$venta->sifen_kude_path || !Storage::disk('public')->exists($venta->sifen_kude_path)) {
            return redirect()->back()->with('error', 'El archivo KuDE no existe físicamente en el servidor. Intente emitir de nuevo si es posible.');
        }

        return Storage::disk('public')->download(
            $venta->sifen_kude_path, 
            "KuDE_{$venta->cdc_sifen}.pdf"
        );
    }

    public function downloadXml(int $id)
    {
        $venta = SaleModel::findOrFail($id);

        if (!$venta->sifen_xml_path || !Storage::disk('public')->exists($venta->sifen_xml_path)) {
            abort(404, 'XML no encontrado.');
        }

        return Storage::disk('public')->download(
            $venta->sifen_xml_path, 
            "DE_{$venta->cdc_sifen}.xml"
        );
    }

    public function reintentarPendientes()
    {
        $pendientes = SaleModel::where('tiene_factura_electronica', false)
            ->where('estado', 'COMPLETADO')
            ->whereNull('deleted_at')
            ->limit(10)
            ->get();

        $ok = 0;
        $errores = 0;

        foreach ($pendientes as $venta) {
            try {
                $result = $this->sifen->emitirFactura($venta);
                if ($result['estado'] === 'APROBADO') $ok++;
                else $errores++;
            } catch (\Throwable $e) {
                $venta->update(['sifen_error' => $e->getMessage(), 'estado_sifen' => 'ERROR']);
                $errores++;
            }
        }

        $msg = "Procesamiento finalizado: {$ok} Exitosos, {$errores} Fallidos/Rechazados.";
        $type = $ok > 0 ? 'success' : 'error';

        return redirect()->route('sifen.index')->with($type, $msg);
    }
}
