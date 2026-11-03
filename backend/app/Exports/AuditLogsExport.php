<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuditLogsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private Collection $logs)
    {
    }

    public function collection(): Collection
    {
        return $this->logs;
    }

    public function headings(): array
    {
        return ['Data', 'Hora', 'Usuário', 'Email', 'Ação', 'Descrição', 'IP'];
    }

    public function map($log): array
    {
        return [
            $log->created_at->format('d/m/Y'),
            $log->created_at->format('H:i:s'),
            $log->user?->name ?? 'Sistema',
            $log->user?->email ?? '-',
            $log->action,
            $log->description ?? '',
            $log->ip_address ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');

        return [];
    }
}   