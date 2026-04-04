<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReporteService;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    protected $service;

    public function __construct(ReporteService $service)
    {
        $this->service = $service;
    }

    public function dashboard()
    {
        $stats = $this->service->obtenerStatsDashboard();
        $recientes = $this->service->obtenerVentasRecientes();

        return $this->successResponse([
            'stats'     => $stats,
            'recientes' => $recientes
        ]);
    }
}
