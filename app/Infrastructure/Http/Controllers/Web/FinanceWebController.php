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
    /** Etiquetas legibles del origen (ref_type) de un movimiento de caja. */
    public const REF_LABELS = [
        'venta'            => 'Venta',
        'cuota'            => 'Cuota',
        'recibo_cuota'     => 'Cobro de cuota',
        'compra'           => 'Compra',
        'comision'         => 'Comisión',
        'factura_gasto_op' => 'Gasto local',
        'factura_vehiculo' => 'Gasto vehículo',
        'factura_mixto'    => 'Gasto mixto',
        'transferencia'    => 'Transferencia',
        'manual'           => 'Manual',
    ];

    public function __construct(
        private readonly CajaService       $cajas,
        private readonly CurrencyConverter $currency,
    ) {}

    // ── Dashboard ───────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $chica   = $this->buildCajaResumen('CAJA_CHICA');
        $capital = $this->buildCajaResumen('CAJA_CAPITAL');

        // Las transferencias entre cajas no son ingresos ni egresos de la empresa.
        $sinTransferencias = fn () => DB::table('movimientos_caja')
            ->whereNull('deleted_at')
            ->where(fn ($q) => $q->whereNull('ref_type')->orWhere('ref_type', '!=', 'transferencia'));

        $ingresosMes = $sinTransferencias()->where('tipo', 'INGRESO')
            ->where('created_at', '>=', now()->startOfMonth())->sum('monto_usd');

        $egresosMes = $sinTransferencias()->where('tipo', 'EGRESO')
            ->where('created_at', '>=', now()->startOfMonth())->sum('monto_usd');

        $cuotasVencidas = DB::table('cuotas')
            ->where('estado', 'PENDIENTE')
            ->where('fecha_vencimiento', '<', now()->toDateString())
            ->count();

        $flujo7dias = $sinTransferencias()
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

    public function show(Request $request, string $codigo): \Illuminate\View\View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $codigo = strtoupper($codigo);
        $caja   = DB::table('cajas')->where('codigo', $codigo)->firstOrFail();
        $f      = $this->filtros($request);

        if ($request->input('export') === 'csv') {
            return $this->exportarCsv($caja, $f);
        }

        $saldo = $this->cajas->saldo($caja->id);

        $movimientos    = $this->movimientosFiltrados($caja->id, $f)->orderByDesc('created_at')->paginate(30)->withQueryString();
        $totalesPeriodo = $this->movimientosFiltrados($caja->id, $f)
            ->selectRaw(
                "SUM(CASE WHEN tipo='INGRESO' THEN monto_usd ELSE 0 END) as ingresos_usd,
                 SUM(CASE WHEN tipo='EGRESO'  THEN monto_usd ELSE 0 END) as egresos_usd"
            )->first();

        return view('finance.caja', compact('caja', 'saldo', 'movimientos', 'totalesPeriodo') + $f + [
            'refLabels'     => self::REF_LABELS,
            'saldosPeriodo' => $this->saldosPeriodo($caja->id, $f),
        ]);
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

        $pdf = Pdf::loadView('pdfs.recibo-movimiento-caja', compact('movimiento', 'caja', 'empresa', 'usuario') + ['refLabels' => self::REF_LABELS])
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

        $f = $this->filtros($request);
        ['desde' => $desde, 'hasta' => $hasta, 'tipoFil' => $tipoFil, 'origenFil' => $origenFil, 'monedaFil' => $monedaFil] = $f;

        $movimientos = $this->movimientosFiltrados($caja->id, $f)->orderBy('created_at')->get();

        $totales = $this->movimientosFiltrados($caja->id, $f)
            ->selectRaw("
                SUM(CASE WHEN tipo='INGRESO' THEN monto_usd ELSE 0 END) as ingresos_usd,
                SUM(CASE WHEN tipo='EGRESO'  THEN monto_usd ELSE 0 END) as egresos_usd,
                SUM(CASE WHEN moneda='PYG' AND tipo='INGRESO' THEN monto ELSE 0 END) as ingresos_pyg,
                SUM(CASE WHEN moneda='PYG' AND tipo='EGRESO'  THEN monto ELSE 0 END) as egresos_pyg
            ")
            ->first();

        $saldosPeriodo = $this->saldosPeriodo($caja->id, $f);
        $saldoActual = $this->cajas->saldo($caja->id);
        $empresa     = EmpresaSettings::get();
        $usuario     = DB::table('users')->where('id', Auth::id())->first();

        $pdf = Pdf::loadView('pdfs.reporte-caja', compact(
            'caja', 'movimientos', 'totales', 'saldoActual',
            'empresa', 'usuario', 'desde', 'hasta', 'tipoFil', 'origenFil', 'monedaFil', 'saldosPeriodo'
        ) + ['refLabels' => self::REF_LABELS])->setPaper('a4', 'portrait');

        $filename = "reporte-{$codigo}-{$desde}-a-{$hasta}.pdf";

        return $pdf->stream($filename);
    }

    // ── Helper privado ──────────────────────────────────────────────────────

    /** Filtros de la caja; los valores fuera de las listas conocidas se ignoran. */
    private function filtros(Request $request): array
    {
        $tipo   = (string) $request->input('tipo', '');
        $origen = (string) $request->input('origen', '');
        $moneda = (string) $request->input('moneda', '');

        return [
            'desde'     => $request->input('desde', now()->startOfMonth()->toDateString()),
            'hasta'     => $request->input('hasta', now()->toDateString()),
            'tipoFil'   => in_array($tipo, ['INGRESO', 'EGRESO'], true) ? $tipo : '',
            'origenFil' => array_key_exists($origen, self::REF_LABELS) ? $origen : '',
            'monedaFil' => in_array($moneda, ['USD', 'PYG', 'BRL'], true) ? $moneda : '',
        ];
    }

    /** Movimientos de la caja con origen y moneda aplicados; con $conPeriodo también fechas y tipo. */
    private function movimientosFiltrados(int $cajaId, array $f, bool $conPeriodo = true): \Illuminate\Database\Query\Builder
    {
        // Columnas calificadas: el CSV une con users, que también tiene created_at/deleted_at.
        return DB::table('movimientos_caja')
            ->where('movimientos_caja.caja_id', $cajaId)
            ->whereNull('movimientos_caja.deleted_at')
            ->when($f['origenFil'], fn ($q, $v) => $q->where('movimientos_caja.ref_type', $v))
            ->when($f['monedaFil'], fn ($q, $v) => $q->where('movimientos_caja.moneda', $v))
            ->when($conPeriodo, fn ($q) => $q
                ->whereDate('movimientos_caja.created_at', '>=', $f['desde'])
                ->whereDate('movimientos_caja.created_at', '<=', $f['hasta'])
                ->when($f['tipoFil'], fn ($q2, $v) => $q2->where('movimientos_caja.tipo', $v)));
    }

    /**
     * Saldo inicial (neto acumulado antes de "desde") y final (inicial + neto del período).
     * Con moneda filtrada se calcula en esa moneda; si no, en USD. No aplica el filtro de tipo:
     * un saldo solo de ingresos o solo de egresos no tiene sentido.
     */
    private function saldosPeriodo(int $cajaId, array $f): array
    {
        $col  = $f['monedaFil'] ? 'monto' : 'monto_usd';
        $neto = "SUM(CASE WHEN tipo='INGRESO' THEN {$col} ELSE -{$col} END)";

        $inicial = (float) $this->movimientosFiltrados($cajaId, $f, false)
            ->whereDate('created_at', '<', $f['desde'])
            ->selectRaw("{$neto} as neto")->value('neto');

        $periodo = (float) $this->movimientosFiltrados($cajaId, $f, false)
            ->whereDate('created_at', '>=', $f['desde'])
            ->whereDate('created_at', '<=', $f['hasta'])
            ->selectRaw("{$neto} as neto")->value('neto');

        return [
            'moneda'  => $f['monedaFil'] ?: 'USD',
            'inicial' => round($inicial, 2),
            'final'   => round($inicial + $periodo, 2),
        ];
    }

    /** CSV (; y BOM UTF-8 para Excel en español) de los movimientos filtrados. */
    private function exportarCsv(object $caja, array $f): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filas = $this->movimientosFiltrados($caja->id, $f)
            ->leftJoin('users', 'users.id', '=', 'movimientos_caja.created_by')
            ->select('movimientos_caja.*', 'users.name as usuario')
            ->orderBy('movimientos_caja.created_at')
            ->cursor();

        // Evita que Excel interprete el texto como fórmula.
        $celda = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v;
        $num   = fn ($v) => number_format((float) $v, 2, ',', '');

        return response()->streamDownload(function () use ($filas, $celda, $num) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Fecha', 'Tipo', 'Origen', 'Concepto', 'Moneda', 'Monto', 'Monto USD', 'Usuario'], ';');
            foreach ($filas as $m) {
                fputcsv($out, array_map($celda, [
                    \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i'),
                    $m->tipo,
                    self::REF_LABELS[$m->ref_type] ?? ($m->ref_type ?? ''),
                    $m->concepto,
                    $m->moneda,
                    $num($m->monto),
                    $num($m->monto_usd),
                    $m->usuario ?? '',
                ]), ';');
            }
            fclose($out);
        }, "movimientos-{$caja->codigo}-{$f['desde']}-a-{$f['hasta']}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

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
