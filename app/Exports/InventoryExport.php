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

class InventoryExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function collection(): Collection
    {
        return collect(DB::table('vehiculos')
            ->whereNotIn('estado', ['VENDIDO', 'BAJA'])
            ->whereNull('deleted_at')
            ->selectRaw("
                numero_chasis, marca, modelo, anio, estado,
                costo_origen_usd, total_gastos_usd,
                (costo_origen_usd + total_gastos_usd) as valor_libro_usd,
                precio_venta_sugerido_usd,
                DATEDIFF(CURDATE(), created_at) as dias_en_stock
            ")
            ->orderByDesc('dias_en_stock')
            ->get()
            ->map(fn($r) => [
                $r->numero_chasis, $r->marca, $r->modelo, $r->anio, $r->estado,
                (float) $r->costo_origen_usd, (float) $r->total_gastos_usd,
                (float) $r->valor_libro_usd,
                (float) ($r->precio_venta_sugerido_usd ?? 0),
                (int) $r->dias_en_stock,
            ])
            ->toArray()
        );
    }

    public function headings(): array
    {
        return [
            'Chasis', 'Marca', 'Modelo', 'Año', 'Estado',
            'Costo USD', 'Gastos USD', 'Valor Libro USD',
            'Precio Sugerido USD', 'Días en Stock',
        ];
    }

    public function title(): string
    {
        return 'Inventario Disponible';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]],
        ];
    }
}
