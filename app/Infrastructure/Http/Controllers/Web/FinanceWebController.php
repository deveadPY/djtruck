<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Web;

use App\Domain\Finance\Services\CajaService;
use App\Infrastructure\Currency\CurrencyConverter;
use App\Infrastructure\Settings\EmpresaSettings;
use App\Domain\Shared\ValueObjects\Currency;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class FinanceWebController extends Controller
{
    public function __construct(
        private readonly CajaService       $cajas,
        private readonly CurrencyConverter $currency,
    ) {}

    // ── Dashboard ───────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $chica   = $this->buildCajaResumen('CAJA_CHICA');
        $capital = $this->buildCajaResumen('CAJA_CAPITAL');

        $ingresosMes = DB::table('movimientos_caja')
            ->whereNull('deleted_at')->where('tipo', 'INGRESO')
            ->where('created_at', '>=', now()->startOfMonth())->sum('monto_usd');

        $egresosMes = DB::table('movimientos_caja')
            ->whereNull('deleted_at')->where('tipo', 'EGRESO')
            ->where('created_at', '>=', now()->startOfMonth())->sum('monto_usd');

        $cuotasVencidas = DB::table('cuotas')
            ->where('estado', 'PENDIENTE')
            ->where('fecha_vencimiento', '<', now()->toDateString())
            ->count();

        $flujo7dias = DB::table('movimientos_caja')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw(
                "DATE(created_at) as fecha,
                 SUM(CASE WHEN tipo='INGRESO' THEN monto_usd ELSE 0 END) as ingresos,
                 SUM(CASE WHEN tipo='EGRESO'  THEN monto_usd ELSE 0 END) as egresos"
            )
            ->groupByRaw('DATE(created_at)')
            ->orderBy('fecha')
            ->get();

        return view('finance.index', compact(
            'chica', 'capital', 'ingresosMes', 'egresosMes',
            'cuotasVencidas', 'flujo7dias'
        ));
    }

    // ── Detalle de caja ─────────────────────────────────────────────────────

    public function show(Request $request, string $codigo): \Illuminate\View\View
    {
        $codigo = strtoupper($codigo);
        $caja   = DB::table('cajas')->where('codigo', $codigo)->firstOrFail();
        $saldo  = $this->cajas->saldo($caja->id);

        $desde   = $request->input('desde', now()->startOfMonth()->toDateString());
        $hasta   = $request->input('hasta', now()->toDateString());
        $tipoFil = $request->input('tipo', '');

        $query = DB::table('movimientos_caja')
            ->where('caja_id', $caja->id)
            ->whereNull('deleted_at')
            ->whereDate('created_at', '>=', $desde)
            ->whereDate('created_at', '<=', $hasta);

        if ($tipoFil) {
            $query->where('tipo', $tipoFil);
        }

        $movimientos    = $query->orderByDesc('created_at')->paginate(30)->withQueryString();
        $totalesPeriodo = DB::table('movimientos_caja')
            ->where('caja_id', $caja->id)->whereNull('deleted_at')
            ->whereDate('created_at', '>=', $desde)
            ->whereDate('created_at', '<=', $hasta)
            ->selectRaw(
                "SUM(CASE WHEN tipo='INGRESO' THEN monto_usd ELSE 0 END) as ingresos_usd,
                 SUM(CASE WHEN tipo='EGRESO'  THEN monto_usd ELSE 0 END) as egresos_usd"
            )->first();

        return view('finance.caja', compact(
            'caja', 'saldo', 'movimientos',
            'totalesPeriodo', 'desde', 'hasta', 'tipoFil'
        ));
    }

    // ── Movimiento manual ───────────────────────────────────────────────────

    public function storeMovimiento(Request $request, string $codigo): \Illuminate\Http\RedirectResponse
    {
        $v = $request->validate([
            'tipo'     => 'required|in:INGRESO,EGRESO',
            'concepto' => 'required|string|max:300',
            'moneda'   => 'required|in:USD,PYG,BRL',
            'monto'    => 'required|numeric|min:0.01',
        ]);

        $caja     = DB::table('cajas')->where('codigo', strtoupper($codigo))->firstOrFail();
        $moneda   = Currency::from($v['moneda']);
        $montoUsd = $moneda === Currency::USD
            ? (float) $v['monto']
            : $this->currency->toBaseCurrency((float) $v['monto'], $moneda)->amount;

        $movId = DB::table('movimientos_caja')->insertGetId([
            'caja_id'    => $caja->id,
            'tipo'       => $v['tipo'],
            'concepto'   => $v['concepto'],
            'moneda'     => $v['moneda'],
            'monto'      => $v['monto'],
            'monto_usd'  => round($montoUsd, 4),
            'ref_type'   => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => Auth::id(),
        ]);

        $label = $v['tipo'] === 'INGRESO' ? 'Ingreso' : 'Egreso';

        return redirect()
            ->route('finance.caja.show', $codigo)
            ->with('success', "{$label} registrado correctamente.")
            ->with('recibo_id', $movId);
    }

    // ── Recibo de movimiento manual ─────────────────────────────────────────

    public function reciboMovimiento(string $codigo, int $id): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
        $caja       = DB::table('cajas')->where('codigo', strtoupper($codigo))->firstOrFail();
        $movimiento = DB::table('movimientos_caja')
            ->where('id', $id)
            ->where('caja_id', $caja->id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $usuario = DB::table('users')->where('id', $movimiento->created_by)->first();
        $empresa = EmpresaSettings::get();

        $pdf = Pdf::loadView('pdfs.recibo-movimiento-caja', compact('movimiento', 'caja', 'empresa', 'usuario'))
            ->setPaper('a5', 'portrait');

        // Autoprint Script
        $pdf->render();
        $canvas = $pdf->getCanvas();
        $canvas->get_cpdf()->openObject();
        $canvas->get_cpdf()->addJavascript("print(true);");
        $canvas->get_cpdf()->closeObject();

        return $pdf->stream("recibo-caja-{$id}.pdf");
    }

    // ── Transferencia entre cajas ───────────────────────────────────────────

    public function transferir(Request $request): \Illuminate\Http\RedirectResponse
    {
        $v = $request->validate([
            'origen'   => 'required|in:CAJA_CHICA,CAJA_CAPITAL',
            'destino'  => 'required|in:CAJA_CHICA,CAJA_CAPITAL|different:origen',
            'moneda'   => 'required|in:USD,PYG,BRL',
            'monto'    => 'required|numeric|min:0.01',
            'concepto' => 'nullable|string|max:255',
        ]);

        $moneda   = Currency::from($v['moneda']);
        $montoUsd = $moneda === Currency::USD
            ? (float) $v['monto']
            : $this->currency->toBaseCurrency((float) $v['monto'], $moneda)->amount;

        $concepto = $v['concepto'] ?? "Transferencia de {$v['origen']} a {$v['destino']}";

        DB::transaction(function () use ($v, $montoUsd, $concepto) {
            $origenId  = $this->cajas->cajaId($v['origen']);
            $destinoId = $this->cajas->cajaId($v['destino']);
            $base = [
                'concepto'   => $concepto,
                'moneda'     => $v['moneda'],
                'monto'      => $v['monto'],
                'monto_usd'  => round($montoUsd, 4),
                'ref_type'   => 'transferencia',
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => Auth::id(),
            ];
            DB::table('movimientos_caja')->insert([
                array_merge($base, ['caja_id' => $origenId,  'tipo' => 'EGRESO']),
                array_merge($base, ['caja_id' => $destinoId, 'tipo' => 'INGRESO']),
            ]);
        });

        return redirect()->route('finance.index')->with('success', 'Transferencia realizada correctamente.');
    }

    // ── Reporte PDF de caja ──────────────────────────────────────────────────

    public function reportePdf(Request $request, string $codigo): \Illuminate\Http\Response
    {
        $codigo = strtoupper($codigo);
        $caja   = DB::table('cajas')->where('codigo', $codigo)->firstOrFail();

        $desde   = $request->input('desde', now()->startOfMonth()->toDateString());
        $hasta   = $request->input('hasta', now()->toDateString());
        $tipoFil = $request->input('tipo', '');

        $query = DB::table('movimientos_caja')
            ->where('caja_id', $caja->id)
            ->whereNull('deleted_at')
            ->whereDate('created_at', '>=', $desde)
            ->whereDate('created_at', '<=', $hasta);

        if ($tipoFil) {
            $query->where('tipo', $tipoFil);
        }

        $movimientos = $query->orderBy('created_at')->get();

        $totales = DB::table('movimientos_caja')
            ->where('caja_id', $caja->id)
            ->whereNull('deleted_at')
            ->whereDate('created_at', '>=', $desde)
            ->whereDate('created_at', '<=', $hasta)
            ->when($tipoFil, fn ($q) => $q->where('tipo', $tipoFil))
            ->selectRaw("
                SUM(CASE WHEN tipo='INGRESO' THEN monto_usd ELSE 0 END) as ingresos_usd,
                SUM(CASE WHEN tipo='EGRESO'  THEN monto_usd ELSE 0 END) as egresos_usd,
                SUM(CASE WHEN moneda='PYG' AND tipo='INGRESO' THEN monto ELSE 0 END) as ingresos_pyg,
                SUM(CASE WHEN moneda='PYG' AND tipo='EGRESO'  THEN monto ELSE 0 END) as egresos_pyg
            ")
            ->first();

        $saldoActual = $this->cajas->saldo($caja->id);
        $empresa     = EmpresaSettings::get();
        $usuario     = DB::table('users')->where('id', Auth::id())->first();

        $pdf = Pdf::loadView('pdfs.reporte-caja', compact(
            'caja', 'movimientos', 'totales', 'saldoActual',
            'empresa', 'usuario', 'desde', 'hasta', 'tipoFil'
        ))->setPaper('a4', 'portrait');

        $filename = "reporte-{$codigo}-{$desde}-a-{$hasta}.pdf";

        return $pdf->stream($filename);
    }

    // ── Helper privado ──────────────────────────────────────────────────────

    private function buildCajaResumen(string $codigo): object
    {
        $caja      = DB::table('cajas')->where('codigo', $codigo)->first();
        $saldo     = $this->cajas->saldo($caja->id);
        $recientes = DB::table('movimientos_caja')
            ->where('caja_id', $caja->id)->whereNull('deleted_at')
            ->orderByDesc('created_at')->limit(5)->get();

        $mes = DB::table('movimientos_caja')
            ->where('caja_id', $caja->id)->whereNull('deleted_at')
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw(
                "SUM(CASE WHEN tipo='INGRESO' THEN monto_usd ELSE 0 END) as ingresos_mes,
                 SUM(CASE WHEN tipo='EGRESO'  THEN monto_usd ELSE 0 END) as egresos_mes"
            )->first();

        return (object) array_merge(
            (array) $caja,
            $saldo,
            [
                'recientes'    => $recientes,
                'ingresos_mes' => round((float) ($mes->ingresos_mes ?? 0), 2),
                'egresos_mes'  => round((float) ($mes->egresos_mes  ?? 0), 2),
            ]
        );
    }
}
