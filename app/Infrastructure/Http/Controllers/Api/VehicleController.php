<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Infrastructure\Currency\CurrencyConverter;
use App\Infrastructure\Http\Resources\VehicleResource;
use App\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use App\Infrastructure\Persistence\Eloquent\Models\VehicleExpenseModel;
use App\Domain\Shared\ValueObjects\Currency;
use App\Domain\Shared\Exceptions\InsufficientStockException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class VehicleController extends BaseApiController
{
    public function __construct(private readonly CurrencyConverter $currency) {}

    public function index(Request $request): JsonResponse
    {
        $query = VehicleModel::query()->withSum('gastos as total_gastos_calc', 'monto_usd');

        if ($request->has('estado'))  $query->where('estado', $request->estado);
        if ($request->has('marca'))   $query->where('marca', 'like', "%{$request->marca}%");
        if ($request->has('modelo'))  $query->where('modelo', 'like', "%{$request->modelo}%");

        $vehicles = $query->with(['proveedor'])->orderByDesc('created_at')->paginate(20);

        return $this->paginatedResponse($vehicles, 'OK', VehicleResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero_chasis'    => 'required|string|max:17|unique:vehiculos,numero_chasis',
            'marca'            => 'required|string|max:80',
            'modelo'           => 'required|string|max:80',
            'anio'              => 'required|integer|min:1950|max:' . (date('Y') + 2),
            'moneda_costo'     => 'required|in:USD,PYG,BRL',
            'costo_origen_moneda' => 'required|numeric|min:0',
            'proveedor_id'     => 'nullable|exists:proveedores,id',
            'tipo_vehiculo'    => 'nullable|string',
            'kilometraje'      => 'nullable|integer|min:0',
        ]);

        // Convertir costo a USD
        $moneda = Currency::from($validated['moneda_costo']);
        $costoUsd = $moneda === Currency::USD
            ? $validated['costo_origen_moneda']
            : $this->currency->toBaseCurrency($validated['costo_origen_moneda'], $moneda)->amount;

        $vehicle = VehicleModel::create(array_merge($validated, [
            'costo_origen_usd' => $costoUsd,
            'total_gastos_usd' => 0,
            'estado'           => 'EN_TRANSITO',
            'created_by'       => auth()->id(),
        ]));

        return $this->successResponse(new VehicleResource($vehicle), 'Vehículo registrado exitosamente.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $vehicle = VehicleModel::with(['gastos', 'proveedor', 'imagenes'])->findOrFail($id);
        return $this->successResponse(new VehicleResource($vehicle));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vehicle = VehicleModel::findOrFail($id);
        $validated = $request->validate([
            'estado'     => 'sometimes|string',
            'ubicacion'  => 'sometimes|string|max:100',
            'kilometraje'=> 'sometimes|integer|min:0',
            'color'      => 'sometimes|string|max:50',
            'precio_venta_sugerido_usd' => 'sometimes|numeric|min:0',
        ]);
        $vehicle->update(array_merge($validated, ['updated_by' => auth()->id()]));
        return $this->successResponse(new VehicleResource($vehicle->refresh()), 'Vehículo actualizado.');
    }

    public function destroy(int $id): JsonResponse
    {
        $vehicle = VehicleModel::findOrFail($id);
        if ($vehicle->estado === 'VENDIDO') {
            return $this->errorResponse('No se puede eliminar un vehículo vendido.', null, 409);
        }
        $vehicle->update(['deleted_by' => auth()->id()]);
        $vehicle->delete();
        return $this->successResponse(null, 'Vehículo eliminado.');
    }

    public function bookValue(int $id): JsonResponse
    {
        $vehicle = VehicleModel::with('gastos')->findOrFail($id);

        $detalle = $vehicle->gastos()->where('aplicado_al_costo', true)->get()->map(fn($g) => [
            'id'        => $g->id,
            'concepto'  => $g->concepto,
            'categoria' => $g->categoria,
            'monto_usd' => $g->monto_usd,
            'fecha'     => $g->fecha_gasto,
            'origen'    => $g->origen_tipo,
        ]);

        return $this->successResponse([
            'vehiculo_id'        => $id,
            'chasis'             => $vehicle->numero_chasis,
            'costo_origen_usd'   => $vehicle->costo_origen_usd,
            'total_gastos_usd'   => $vehicle->total_gastos_usd,
            'valor_libro_usd'    => $vehicle->valor_libro_usd,
            'precio_sugerido_usd'=> $vehicle->precio_venta_sugerido_usd,
            'margen_potencial_usd'=> $vehicle->precio_venta_sugerido_usd
                ? round($vehicle->precio_venta_sugerido_usd - $vehicle->valor_libro_usd, 2)
                : null,
            'detalle_gastos'     => $detalle,
            'monedas'            => [
                'USD' => $this->currency->format((float)$vehicle->valor_libro_usd, Currency::USD),
                'PYG' => $this->currency->format(
                    $this->currency->fromBaseCurrency((float)$vehicle->valor_libro_usd, Currency::PYG)->amount,
                    Currency::PYG
                ),
            ],
        ]);
    }

    public function expenses(int $id): JsonResponse
    {
        $vehicle  = VehicleModel::findOrFail($id);
        $expenses = VehicleExpenseModel::where('vehiculo_id', $id)
            ->orderByDesc('fecha_gasto')->paginate(20);
        return $this->paginatedResponse($expenses);
    }

    public function addExpense(Request $request, int $id): JsonResponse
    {
        $vehicle = VehicleModel::findOrFail($id);
        $validated = $request->validate([
            'concepto'          => 'required|string|max:255',
            'categoria'         => 'required|string',
            'origen_tipo'       => 'required|string',
            'moneda'            => 'required|in:USD,PYG,BRL',
            'monto_moneda'      => 'required|numeric|min:0.01',
            'fecha_gasto'       => 'required|date',
            'aplicado_al_costo' => 'required|boolean',
            'factura_proveedor_id' => 'nullable|exists:facturas_proveedores,id',
            'repuesto_id'       => 'nullable|exists:stock_repuestos,id',
            'observaciones'     => 'nullable|string',
        ]);

        $moneda   = Currency::from($validated['moneda']);
        $montoUsd = $moneda === Currency::USD
            ? $validated['monto_moneda']
            : $this->currency->toBaseCurrency($validated['monto_moneda'], $moneda)->amount;

        $expense = VehicleExpenseModel::create(array_merge($validated, [
            'vehiculo_id' => $id,
            'monto_usd'   => $montoUsd,
            'aplicado_en' => $validated['aplicado_al_costo'] ? now() : null,
            'created_by'  => auth()->id(),
        ]));

        $vehicle->refresh();

        return $this->successResponse([
            'gasto'           => $expense,
            'nuevo_valor_libro_usd' => $vehicle->valor_libro_usd,
        ], 'Gasto agregado al vehículo.', 201);
    }

    public function removeExpense(int $id, int $expenseId): JsonResponse
    {
        $expense = VehicleExpenseModel::where('vehiculo_id', $id)->findOrFail($expenseId);
        $expense->update(['deleted_by' => auth()->id()]);
        $expense->delete();
        return $this->successResponse(null, 'Gasto eliminado.');
    }

    public function available(Request $request): JsonResponse
    {
        $vehicles = VehicleModel::whereIn('estado', ['DISPONIBLE', 'RESERVADO'])
            ->when($request->marca,  fn($q) => $q->where('marca', $request->marca))
            ->when($request->modelo, fn($q) => $q->where('modelo', 'like', "%{$request->modelo}%"))
            ->orderBy('marca')->orderBy('modelo')->paginate(20);

        return $this->paginatedResponse($vehicles, 'Vehículos disponibles.');
    }

    public function tradeIns(): JsonResponse
    {
        $vehicles = VehicleModel::where('estado', 'TOMA')
            ->with('ventaOrigen')->orderByDesc('created_at')->paginate(20);
        return $this->paginatedResponse($vehicles, 'Vehículos recibidos como canje.');
    }
}
