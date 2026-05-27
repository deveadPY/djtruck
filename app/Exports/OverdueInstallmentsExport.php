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

class OverdueInstallmentsExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function collection(): Collection
    {
        return collect(DB::table('cuotas')
            ->join('planes_cuotas', 'cuotas.plan_cuotas_id', '=', 'planes_cuotas.id')
            ->join('ventas', 'cuotas.venta_id', '=', 'ventas.id')
            ->join('clientes', 'planes_cuotas.cliente_id', '=', 'clientes.id')
            ->whereIn('cuotas.estado', ['VENCIDA', 'EN_MORA'])
            ->whereNull('cuotas.deleted_at')
            ->selectRaw("
                clientes.razon_social, clientes.telefono, clientes.email,
                ventas.numero_venta,
                cuotas.numero_cuota, cuotas.total_cuotas,
                cuotas.fecha_vencimiento,
                DATEDIFF(CURDATE(), cuotas.fecha_vencimiento) as dias_mora,
                (cuotas.capital + cuotas.interes + cuotas.interes_mora - cuotas.monto_pagado) as saldo_usd
            ")
            ->orderByDesc('dias_mora')
            ->get()
            ->map(fn($r) => [
                $r->razon_social, $r->telefono ?? '—', $r->email ?? '—',
                $r->numero_venta,
                "{$r->numero_cuota}/{$r->total_cuotas}",
                $r->fecha_vencimiento, (int) $r->dias_mora,
                round((float) $r->saldo_usd, 2),
            ])
            ->toArray()
        );
    }

    public function headings(): array
    {
        return [
            'Cliente', 'Teléfono', 'Email',
            'N° Venta', 'Cuota',
            'Vencimiento', 'Días Mora', 'Saldo USD',
        ];
    }

    public function title(): string
    {
        return 'Cuotas Vencidas';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C00000']]],
        ];
    }
}
