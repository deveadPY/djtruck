<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesSummaryExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(
        private readonly string $desde,
        private readonly string $hasta,
    ) {}

    public function collection(): Collection
    {
        return collect(DB::table('ventas')
            ->leftJoin('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->leftJoin('vehiculos', 'ventas.vehiculo_id', '=', 'vehiculos.id')
            ->leftJoin('users', 'ventas.vendedor_id', '=', 'users.id')
            ->whereBetween('ventas.fecha_venta', [$this->desde, $this->hasta])
            ->where('ventas.estado', 'COMPLETADO')
            ->whereNull('ventas.deleted_at')
            ->selectRaw("
                ventas.numero_venta,
                ventas.fecha_venta,
                clientes.razon_social as cliente,
                CONCAT(COALESCE(vehiculos.marca,''),' ',COALESCE(vehiculos.modelo,'')) as vehiculo,
                users.name as vendedor,
                ventas.modalidad_pago,
                ventas.precio_venta_usd,
                ventas.descuento_usd,
                ventas.valor_libro_snapshot,
                ventas.margen_bruto_usd,
                ventas.margen_pct
            ")
            ->orderBy('ventas.fecha_venta')
            ->get()
            ->map(fn($r) => [
                $r->numero_venta,
                $r->fecha_venta,
                $r->cliente,
                trim($r->vehiculo),
                $r->vendedor ?? '—',
                $r->modalidad_pago,
                (float) $r->precio_venta_usd,
                (float) $r->descuento_usd,
                (float) $r->valor_libro_snapshot,
                (float) $r->margen_bruto_usd,
                round((float) $r->margen_pct, 2) . '%',
            ])
            ->toArray()
        );
    }

    public function headings(): array
    {
        return [
            'N° Venta', 'Fecha', 'Cliente', 'Vehículo', 'Vendedor',
            'Modalidad', 'Precio USD', 'Descuento USD',
            'Valor Libro USD', 'Margen USD', 'Margen %',
        ];
    }

    public function title(): string
    {
        return "Ventas {$this->desde} a {$this->hasta}";
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]],
        ];
    }
}
