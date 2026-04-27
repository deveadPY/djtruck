<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use App\Infrastructure\Persistence\Eloquent\Models\SupplierInvoiceModel;
use App\Infrastructure\Currency\CurrencyConverter;
use App\Domain\Shared\ValueObjects\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupplierController extends BaseApiController
{
    public function __construct(private readonly CurrencyConverter $currency) {}

    public function index(Request $request): JsonResponse
    {
        $suppliers = SupplierModel::query()
            ->when($request->pais, fn($q) => $q->where('pais', $request->pais))
            ->when($request->activo !== null, fn($q) => $q->where('activo', $request->activo))
            ->orderBy('razon_social')->paginate(20);
        return $this->paginatedResponse($suppliers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ruc_rut_nit'      => 'nullable|string|max:30',
            'razon_social'     => 'required|string|max:200',
            'nombre_fantasia'  => 'nullable|string|max:200',
            'pais'             => 'required|string|size:2',
            'tipo'             => 'required|in:FABRICANTE,DISTRIBUIDOR,IMPORTADOR,SERVICIO,OTRO',
            'moneda_principal' => 'required|in:USD,PYG,BRL',
            'email'            => 'nullable|email|max:150',
            'telefono'         => 'nullable|string|max:50',
        ]);
        $supplier = SupplierModel::create(array_merge($validated, ['created_by' => auth()->id()]));
        return $this->successResponse($supplier, 'Proveedor creado.', 201);
    }

    public function show(int $id): JsonResponse
    {
        return $this->successResponse(SupplierModel::with(['facturas', 'vehiculos'])->findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $supplier = SupplierModel::findOrFail($id);
        $supplier->update($request->only(['razon_social', 'nombre_fantasia', 'email', 'telefono', 'activo']));
        return $this->successResponse($supplier, 'Proveedor actualizado.');
    }

    public function destroy(int $id): JsonResponse
    {
        SupplierModel::findOrFail($id)->delete();
        return $this->successResponse(null, 'Proveedor eliminado.');
    }

    public function invoices(int $id): JsonResponse
    {
        $invoices = SupplierInvoiceModel::where('proveedor_id', $id)
            ->orderByDesc('fecha_factura')->paginate(20);
        return $this->paginatedResponse($invoices);
    }

    public function addInvoice(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'numero_factura' => 'required|string|max:50',
            'fecha_factura'  => 'required|date',
            'destino'        => 'required|in:VEHICULO,GASTO_OPERATIVO,MIXTO',
            'vehiculo_id'    => 'required_if:destino,VEHICULO|nullable|exists:vehiculos,id',
            'cuenta_gasto'   => 'nullable|string|max:100',
            'moneda'         => 'required|in:USD,PYG,BRL',
            'subtotal'       => 'required|numeric|min:0',
            'impuestos'      => 'nullable|numeric|min:0',
            'descripcion'    => 'nullable|string',
        ]);

        $moneda   = Currency::from($validated['moneda']);
        $total    = ($validated['subtotal'] + ($validated['impuestos'] ?? 0));
        $totalUsd = $moneda === Currency::USD
            ? $total
            : $this->currency->toBaseCurrency($total, $moneda)->amount;

        $invoice = SupplierInvoiceModel::create(array_merge($validated, [
            'proveedor_id' => $id,
            'impuestos'    => $validated['impuestos'] ?? 0,
            'total_usd'    => $totalUsd,
            'estado'       => 'PENDIENTE',
            'created_by'   => auth()->id(),
        ]));

        return $this->successResponse($invoice, 'Factura registrada.', 201);
    }

    public function updateInvoice(Request $request, int $id, int $invId): JsonResponse
    {
        $invoice = SupplierInvoiceModel::where('proveedor_id', $id)->findOrFail($invId);
        $invoice->update($request->only(['estado', 'descripcion']));
        return $this->successResponse($invoice, 'Factura actualizada.');
    }
}
