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
        $path = $this->argument('path');
        $password = $this->argument('password');

        try {
            $result = $service->convert($path, $password);

            file_put_contents(storage_path('cert.pem'), $result['certificate']);
            file_put_contents(storage_path('key.pem'), $result['privateKey']);

            $this->info('✅ Conversión exitosa');
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}
