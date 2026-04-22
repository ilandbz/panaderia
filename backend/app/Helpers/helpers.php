<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

if (!function_exists('obtenerDatosDniRuc')) {
    /**
     * Obtiene datos de DNI o RUC desde una API externa.
     * 
     * @param string $tipo 'dni' o 'ruc'
     * @param string $numero número de identificación
     * @return \Illuminate\Http\Client\Response|\Illuminate\Http\JsonResponse
     */
    function obtenerDatosDniRuc($tipo, $numero)
    {
        try {
            $token = config('services.apis-net.token');
            $baseUrl = config('services.apis-net.url');

            // Según la documentación de apisperu.net, los endpoints suelen ser /dni o /ruc
            $endpoint = ($tipo === 'dni') ? "/dni?numero=$numero" : "/ruc?numero=$numero";
            $url = $baseUrl . $endpoint;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer $token"
            ])->get($url);

            return $response;
        } catch (\Exception $e) {
            Log::error("Error en obtenerDatosDniRuc: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error al conectar con la API externa.'], 500);
        }
    }
}
