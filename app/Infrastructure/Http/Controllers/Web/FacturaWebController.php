<?php

namespace App\Infrastructure\Http\Controllers\Web;

use App\Domain\Finance\Services\CajaService;
use App\Infrastructure\Http\Requests\StoreFacturaRequest;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FacturaWebController extends Controller
{
    public function __construct(private readonly CajaService $cajas) {}

    public function index(Request $request)
    {
        $query = DB::table('facturas_proveedores')
            ->whereNull('facturas_proveedores.deleted_at')
            ->join('proveedores', 'facturas_proveedores.proveedor_id', '=', 'proveedores.id')
            ->leftJoin('vehiculos', 'facturas_proveedores.vehiculo_id', '=', 'vehiculos.id')
            ->select('facturas_proveedores.*', 'proveedores.razon_social', 'vehiculos.numero_chasis', 'vehiculos.marca', 'vehiculos.modelo');

        if ($request->filled('proveedor_id')) {
            $query->where('facturas_proveedores.proveedor_id', $request->proveedor_id);
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('facturas_proveedores.fecha_factura', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('facturas_proveedores.fecha_factura', '<=', $request->fecha_fin);
        }

        $facturas = $query->latest('facturas_proveedores.fecha_factura')->get();
        $proveedores = DB::table('proveedores')->where('activo', true)->whereNull('deleted_at')->orderBy('razon_social')->get();

        return view('facturas.index', compact('facturas', 'proveedores'));
    }

    public function create()
    {
        $proveedores = DB::table('proveedores')->where('activo', true)->whereNull('deleted_at')->get();
        // Mostrar vehículos que aún pertenecen a la empresa o están en proceso (no vendidos de hace años)
        $vehiculos = DB::table('vehiculos')->whereNull('deleted_at')->whereNotIn('estado', ['BAJA'])->get();
        return view('facturas.create', compact('proveedores', 'vehiculos'));
    }

    public function store(StoreFacturaRequest $request)
    {
        $data = $request->validated();

        $data['impuestos'] = $data['impuestos'] ?? 0;
        $data['created_by'] = Auth::id();

        $categoriaGasto       = $data['categoria_gasto'] ?? null;
        $documentosFiles      = $request->file('documentos') ?? [];
        $documentosDesc       = $data['documentos_descripcion'] ?? null;
        unset($data['categoria_gasto'], $data['documentos'], $data['documentos_descripcion']);

        DB::beginTransaction();
        try {
            $facturaId = DB::table('facturas_proveedores')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);

            // Si el destino es un VEHÍCULO, creamos el gasto de una vez asociado a esa factura
            if ($data['destino'] === 'VEHICULO' && !empty($data['vehiculo_id'])) {
                DB::table('gastos_vehiculo')->insert([
                    'vehiculo_id' => $data['vehiculo_id'],
                    'origen_tipo' => 'FACTURA_PROVEEDOR',
                    'factura_proveedor_id' => $facturaId,
                    'concepto' => $data['cuenta_gasto'] ?? 'Gasto por factura ' . $data['numero_factura'],
                    'descripcion' => $data['descripcion'],
                    'categoria' => $categoriaGasto ?? 'OTROS_PREPARACION',
                    'moneda' => $data['moneda'],
                    'monto_moneda' => $data['subtotal'] + $data['impuestos'],
                    'monto_usd' => $data['total_usd'],
                    'fecha_gasto' => $data['fecha_factura'],
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Recalcular total_gastos_usd del vehículo
                $total = DB::table('gastos_vehiculo')
                    ->where('vehiculo_id', $data['vehiculo_id'])->whereNull('deleted_at')
                    ->sum('monto_usd');
                DB::table('vehiculos')
                    ->where('id', $data['vehiculo_id'])
                    ->update(['total_gastos_usd' => $total, 'updated_at' => now()]);
            }

            // ── Movimiento automático de caja ───────────────────────────────
            $montoTotal = $data['subtotal'] + $data['impuestos'];
            $concepto   = "Gasto #{$data['numero_factura']} - {$data['cuenta_gasto']}";

            if ($data['destino'] === 'GASTO_OPERATIVO' || $data['destino'] === 'MIXTO') {
                // Egreso automático en Caja Chica (gastos operativos y mixtos)
                $this->cajas->egresoChica(
                    concepto:    $concepto,
                    moneda:      $data['moneda'],
                    monto:       (float) $montoTotal,
                    montoUsd:    (float) $data['total_usd'],
                    refId:       $facturaId,
                    refType:     $data['destino'] === 'MIXTO' ? 'factura_mixto' : 'factura_gasto_op',
                );
            } elseif ($data['destino'] === 'VEHICULO' && !empty($data['vehiculo_id'])) {
                // Egreso automático en Caja Capital (inversión en activos)
                $this->cajas->egresoCapital(
                    concepto:    $concepto . " (Vehículo #{$data['vehiculo_id']})",
                    moneda:      $data['moneda'],
                    monto:       (float) $montoTotal,
                    montoUsd:    (float) $data['total_usd'],
                    refId:       $facturaId,
                    refType:     'factura_vehiculo',
                );
            }

            // Guardar documentos adjuntos si se enviaron
            if (!empty($documentosFiles)) {
                $uploadDir = 'uploads/documentos/facturas_proveedores/' . $facturaId;
                foreach ($documentosFiles as $archivo) {
                    $nombre      = time() . '_' . uniqid() . '_' . $archivo->getClientOriginalName();
                    $originalName = $archivo->getClientOriginalName();
                    $mimeType    = $archivo->getClientMimeType();
                    $fileSize    = $archivo->getSize();
                    $archivo->move(public_path($uploadDir), $nombre);
                    DB::table('documentos')->insert([
                        'documentable_type' => 'facturas_proveedores',
                        'documentable_id'   => $facturaId,
                        'ruta'              => $uploadDir . '/' . $nombre,
                        'nombre_original'   => $originalName,
                        'mime_type'         => $mimeType,
                        'tamano_bytes'      => $fileSize,
                        'descripcion'       => $documentosDesc,
                        'created_by'        => Auth::id(),
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('facturas.show', $facturaId)->with('success', 'Factura / Gasto registrado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al registrar la factura: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $factura = DB::table('facturas_proveedores')->where('id', $id)->whereNull('deleted_at')->firstOrFail();

        DB::beginTransaction();
        try {
            // 1. Soft-delete la factura
            DB::table('facturas_proveedores')->where('id', $id)->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Si era gasto de vehículo, soft-delete el gasto y recalcular total
            if ($factura->destino === 'VEHICULO' && $factura->vehiculo_id) {
                DB::table('gastos_vehiculo')
                    ->where('factura_proveedor_id', $id)
                    ->whereNull('deleted_at')
                    ->update(['deleted_at' => now(), 'updated_at' => now()]);

                $total = DB::table('gastos_vehiculo')
                    ->where('vehiculo_id', $factura->vehiculo_id)
                    ->whereNull('deleted_at')
                    ->sum('monto_usd');
                DB::table('vehiculos')
                    ->where('id', $factura->vehiculo_id)
                    ->update(['total_gastos_usd' => $total, 'updated_at' => now()]);
            }

            // 3. Soft-delete el movimiento de caja asociado
            DB::table('movimientos_caja')
                ->where('referencia_id', $id)
                ->whereIn('ref_type', ['factura_gasto_op', 'factura_vehiculo', 'factura_mixto'])
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now(), 'updated_at' => now()]);

            // 4. Soft-delete documentos adjuntos
            DB::table('documentos')
                ->where('documentable_type', 'facturas_proveedores')
                ->where('documentable_id', $id)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now(), 'updated_at' => now()]);

            DB::commit();
            return redirect()->route('facturas.index')->with('success', 'Factura #' . $factura->numero_factura . ' eliminada correctamente. El movimiento de caja fue revertido.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al eliminar: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $factura = DB::table('facturas_proveedores')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
        $factura->proveedor = DB::table('proveedores')->where('id', $factura->proveedor_id)->first();
        if ($factura->vehiculo_id) {
            $factura->vehiculo = DB::table('vehiculos')->where('id', $factura->vehiculo_id)->first();
        }

        $documentos = DB::table('documentos')
            ->where('documentable_type', 'facturas_proveedores')
            ->where('documentable_id', $id)
            ->whereNull('deleted_at')
            ->latest()
            ->get();

        return view('facturas.show', compact('factura', 'documentos'));
    }
}
