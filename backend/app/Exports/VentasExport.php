<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class VentasExport implements WithMultipleSheets
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

    public function sheets(): array
    {
        return [
            new VentasResumenSheet($this->data['ventas'], $this->desde, $this->hasta),
            new VentasDetalleSheet($this->data['detalles']),
        ];
    }
}
