<?php

namespace App\Infrastructure\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Http\Requests\StoreClienteRequest;
use App\Infrastructure\Http\Requests\UpdateClienteRequest;
use App\Infrastructure\Settings\EmpresaSettings;
use Barryvdh\DomPDF\Facade\Pdf;

class ClienteWebController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');

        $query = DB::table('clientes')->whereNull('deleted_at');

        if ($q) {
            $query->where(function ($query) use ($q) {
                $query->where('razon_social', 'like', "%{$q}%")
                      ->orWhere('ruc', 'like', "%{$q}%")
                      ->orWhere('nombre_fantasia', 'like', "%{$q}%");
            });
        }

        $clientes = $query->latest()->paginate(25)->withQueryString();
        return view('clientes.index', compact('clientes', 'q'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(StoreClienteRequest $request)
    {
        $data = $request->validated();

        unset($data['archivos']);
        $data['linea_credito_usd'] = $data['linea_credito_usd'] ?? 0;
        $data['created_by'] = Auth::id();
        $data['activo'] = true;

        $id = DB::table('clientes')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);

        // Handle file uploads
        if ($request->hasFile('archivos')) {
            $uploadDir = 'uploads/documentos/clientes/' . $id;
            foreach ($request->file('archivos') as $archivo) {
                $nombreOriginal = $archivo->getClientOriginalName();
                $mimeType = $archivo->getClientMimeType();
                $tamano = $archivo->getSize();

                $nombre = time() . '_' . uniqid() . '_' . $nombreOriginal;
                $archivo->move(public_path($uploadDir), $nombre);

                DB::table('documentos')->insert([
                    'documentable_type' => 'clientes',
                    'documentable_id' => $id,
                    'ruta' => $uploadDir . '/' . $nombre,
                    'nombre_original' => $nombreOriginal,
                    'mime_type' => $mimeType,
                    'tamano_bytes' => $tamano,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cliente' => ['id' => $id, 'razon_social' => $data['razon_social']]
            ]);
        }

        return redirect()->route('clientes.show', $id)->with('success', 'Cliente registrado exitosamente.');
    }

    public function show($id)
    {
        $cliente = DB::table('clientes')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$cliente) {
            abort(404);
        }

        // Obtener historial de ventas
        $ventas = DB::table('ventas')
            ->join('vehiculos', 'ventas.vehiculo_id', '=', 'vehiculos.id')
            ->where('ventas.cliente_id', $id)
            ->whereNull('ventas.deleted_at')
            ->select('ventas.*', 'vehiculos.marca', 'vehiculos.modelo', 'vehiculos.numero_chasis')
            ->orderBy('ventas.fecha_venta', 'desc')
            ->get();

        // Obtener planes de cuotas activos
        $planes = DB::table('planes_cuotas')
            ->where('cliente_id', $id)
            ->get();

        // Calcular saldo deudor en planes activos (solo cuotas pendientes/mora)
        $saldo_deudor = DB::table('cuotas')
            ->join('planes_cuotas', 'cuotas.plan_cuotas_id', '=', 'planes_cuotas.id')
            ->where('planes_cuotas.cliente_id', $id)
            ->whereIn('cuotas.estado', ['PENDIENTE', 'VENCIDA', 'EN_MORA', 'PAGADA_PARCIAL'])
            ->whereNull('cuotas.deleted_at')
            ->sum(DB::raw('cuotas.capital + cuotas.interes + cuotas.interes_mora - cuotas.monto_pagado'));

        $documentos = DB::table('documentos')
            ->where('documentable_type', 'clientes')
            ->where('documentable_id', $id)
            ->whereNull('deleted_at')
            ->latest()
            ->get();

        $referencias = DB::table('referencias_clientes')
            ->where('cliente_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('tipo')
            ->get();

        return view('clientes.show', compact('cliente', 'ventas', 'planes', 'saldo_deudor', 'documentos', 'referencias'));
    }

    public function edit($id)
    {
        $cliente = DB::table('clientes')->where('id', $id)->whereNull('deleted_at')->first();
        if (!$cliente)
            abort(404);
        return view('clientes.edit', compact('cliente'));
    }

    public function update(UpdateClienteRequest $request, $id)
    {
        $data = $request->validated();

        $data['linea_credito_usd'] = $data['linea_credito_usd'] ?? 0;
        $data['updated_at'] = now();

        DB::table('clientes')->where('id', $id)->update($data);
        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado exitosamente.');
    }

    public function destroy($id)
    {
        DB::table('clientes')->where('id', $id)->update(['deleted_at' => now(), 'activo' => false]);
        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado.');
    }

    public function storeReferencia(Request $request, $clienteId)
    {
        $request->validate([
            'tipo'          => 'required|in:COMERCIAL,PERSONAL',
            'nombre'        => 'required|string|max:150',
            'empresa'       => 'nullable|string|max:150',
            'telefono'      => 'nullable|string|max:30',
            'relacion'      => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
        ]);

        DB::table('referencias_clientes')->insert([
            'cliente_id'    => $clienteId,
            'tipo'          => $request->tipo,
            'nombre'        => $request->nombre,
            'empresa'       => $request->empresa,
            'telefono'      => $request->telefono,
            'relacion'      => $request->relacion,
            'observaciones' => $request->observaciones,
            'created_by'    => Auth::id(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return back()->with('success', 'Referencia agregada correctamente.');
    }

    public function destroyReferencia($clienteId, $refId)
    {
        DB::table('referencias_clientes')
            ->where('id', $refId)
            ->where('cliente_id', $clienteId)
            ->update(['deleted_at' => now()]);

        return back()->with('success', 'Referencia eliminada.');
    }

    public function downloadEstadoCuenta($id)
    {
        $cliente = DB::table('clientes')->where('id', $id)->whereNull('deleted_at')->firstOrFail();

        // Calcular crédito disponible
        $saldoDeudorActual = DB::table('cuotas')
            ->join('planes_cuotas', 'cuotas.plan_cuotas_id', '=', 'planes_cuotas.id')
            ->where('planes_cuotas.cliente_id', $id)
            ->whereIn('cuotas.estado', ['PENDIENTE', 'VENCIDA', 'EN_MORA', 'PAGADA_PARCIAL'])
            ->whereNull('cuotas.deleted_at')
            ->sum(DB::raw('cuotas.capital + cuotas.interes + cuotas.interes_mora - cuotas.monto_pagado'));

        $creditoDisponible = max(0, floatval($cliente->linea_credito_usd ?? 0) - (float) $saldoDeudorActual);

        // Obtener todos los planes activos con sus cuotas
        $planes = DB::table('planes_cuotas')->where('cliente_id', $id)->get();

        $ventasConPlan = [];
        $totalDeuda    = 0;
        $totalPagado   = 0;
        $saldoPendiente= 0;
        $totalMora     = 0;

        foreach ($planes as $plan) {
            $venta   = DB::table('ventas')->where('id', $plan->venta_id)->first();
            $vehiculo= $venta ? DB::table('vehiculos')->where('id', $venta->vehiculo_id)->first() : null;
            $cuotas  = DB::table('cuotas')
                ->where('plan_cuotas_id', $plan->id)
                ->whereNull('deleted_at')
                ->orderBy('numero_cuota')
                ->get();

            $subtotalTotal  = 0;
            $subtotalPagado = 0;
            $subtotalSaldo  = 0;

            foreach ($cuotas as $cuota) {
                $montoTotal  = floatval($cuota->capital) + floatval($cuota->interes) + floatval($cuota->interes_mora);
                $saldoCuota  = max(0, $montoTotal - floatval($cuota->monto_pagado));

                $subtotalTotal  += $montoTotal;
                $subtotalPagado += floatval($cuota->monto_pagado);
                $subtotalSaldo  += $saldoCuota;
                $totalMora      += floatval($cuota->interes_mora);
            }

            $totalDeuda     += $subtotalTotal;
            $totalPagado    += $subtotalPagado;
            $saldoPendiente += $subtotalSaldo;

            if ($venta && $vehiculo) {
                $ventasConPlan[] = [
                    'plan'             => $plan,
                    'venta'            => $venta,
                    'vehiculo'         => $vehiculo,
                    'cuotas'           => $cuotas,
                    'subtotal_total'   => $subtotalTotal,
                    'subtotal_pagado'  => $subtotalPagado,
                    'subtotal_saldo'   => $subtotalSaldo,
                ];
            }
        }

        $empresa = EmpresaSettings::get();

        $pdf = Pdf::loadView('pdfs.estado-cuenta-cliente', compact(
            'cliente', 'ventasConPlan', 'creditoDisponible',
            'totalDeuda', 'totalPagado', 'saldoPendiente', 'totalMora',
            'empresa'
        ))->setPaper('a4', 'portrait');

        $filename = 'estado-cuenta-' . \Illuminate\Support\Str::slug($cliente->razon_social) . '-' . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }
}
