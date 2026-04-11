<?php

namespace App\Console\Commands;

use App\Infrastructure\Sunat\Services\CertificateConverterService;
use Illuminate\Console\Command;

class ConvertPfxToPem extends Command
{
    protected $signature = 'sunat:convert-pfx {path} {password}';
    protected $description = 'Convierte un archivo PFX a PEM';
    public function handle(CertificateConverterService $service)
    {
        // $path = $this->argument('path');

        $path = storage_path('app/' . $this->argument('path'));
        $password = $this->argument('password');

        try {
            $result = $service->convert($path, $password);

            $destinationPath = storage_path('app/certificado/convertido');

            // Crear el directorio si no existe
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            file_put_contents($destinationPath . '/cert.pem', $result['certificate']);
            file_put_contents($destinationPath . '/key.pem', $result['privateKey']);

            $this->info('✅ Conversión exitosa');
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}
