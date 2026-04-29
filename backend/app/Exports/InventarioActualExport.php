<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;

class InventarioActualExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(fn($row) => [
            $row['codigo'],
            $row['nombre'],
            $row['categoria'],
            $row['stock'],
            $row['unidad'],
            $row['precio_venta'],
            $row['costo'],
            $row['tipo'],
            $row['estado_stock'],
        ]);
    }

    public function headings(): array
    {
        return [
            'Código',
            'Nombre',
            'Categoría',
            'Stock Actual',
            'Unidad',
            'Precio Venta',
            'Costo',
            'Tipo',
            'Estado Stock',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C8971A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function title(): string
    {
        return 'Inventario Actual';
    }
}
