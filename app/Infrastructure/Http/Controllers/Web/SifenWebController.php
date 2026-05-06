<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Web;

use App\Infrastructure\SIFEN\ElectronicInvoicingService;
use App\Infrastructure\Persistence\Eloquent\Models\SaleModel;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $totalConError = SaleModel::whereNotNull('sifen_error')
            ->where('tiene_factura_electronica', false)
            ->whereNull('deleted_at')
            ->count();

        // ── Listado según filtro ──────────────────────────────────────────
        $query = SaleModel::with(['vehiculo', 'cliente'])
            ->whereNull('deleted_at')
            ->orderByDesc('fecha_venta');

        $ventas = match($filtro) {
            'emitidas'  => $query->where('tiene_factura_electronica', true)->paginate(20),
            'errores'   => $query->whereNotNull('sifen_error')->where('tiene_factura_electronica', false)->paginate(20),
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

    public function emitir(int $saleId)
    {
        $venta = SaleModel::findOrFail($saleId);

        try {
            $this->sifen->emitirFactura($venta);
            return redirect()->route('sifen.index')->with('success', "Factura emitida para venta #{$venta->numero_venta}.");
        } catch (\Throwable $e) {
            // Guardar el error en la venta para mostrarlo en la vista
            $venta->update(['sifen_error' => $e->getMessage()]);
            return redirect()->route('sifen.index', ['filtro' => 'errores'])
                ->with('error', "Error al emitir #{$venta->numero_venta}: {$e->getMessage()}");
        }
    }

    public function reintentarPendientes()
    {
        $pendientes = SaleModel::where('tiene_factura_electronica', false)
            ->where('estado', 'COMPLETADO')
            ->whereNull('deleted_at')
            ->limit(10)
            ->get();

        $ok = 0;
        $errores = [];

        foreach ($pendientes as $venta) {
            try {
                $this->sifen->emitirFactura($venta);
                $ok++;
            } catch (\Throwable $e) {
                $venta->update(['sifen_error' => $e->getMessage()]);
                $errores[] = "#{$venta->numero_venta}: {$e->getMessage()}";
            }
        }

        $msg = "Re-emisión: {$ok} OK" . ($errores ? ', ' . count($errores) . ' error(es).' : '.');
        $type = $errores ? 'error' : 'success';

        return redirect()->route('sifen.index')->with($type, $msg);
    }
}
