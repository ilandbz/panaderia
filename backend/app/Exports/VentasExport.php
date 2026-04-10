<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;

class VentasExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected array $data;
    protected string $desde;
    protected string $hasta;

    public function __construct(array $data, string $desde, string $hasta)
    {
        $this->data  = $data;
        $this->desde = $desde;
        $this->hasta = $hasta;
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(fn($row) => array_values($row));
    }

    public function headings(): array
    {
        return [
            'N° Venta',
            'Cliente',
            'Forma de Pago',
            'Subtotal (S/)',
            'IGV (S/)',
            'Total (S/)',
            'Estado',
            'Comprobante',
            'Serie',
            'N° Comprobante',
            'Vendedor',
            'Fecha',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C8971A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function title(): string
    {
        return "Ventas {$this->desde} a {$this->hasta}";
    }
}
