<?php

namespace App\Infrastructure\Sunat\Services;

use RuntimeException;

class CertificateConverterService
{
    /**
     * Convierte un archivo .pfx a formato .pem (certificado + clave privada).
     *
     * @param  string  $pfxPath  Ruta absoluta al archivo .pfx
     * @param  string  $password Contraseña del .pfx
     * @return array{certificate: string, privateKey: string}
     */
    public function convert(string $pfxPath, string $password): array
    {
        if (! file_exists($pfxPath)) {
            throw new RuntimeException("Archivo .pfx no encontrado: {$pfxPath}");
        }

        $pfxContent = file_get_contents($pfxPath);

        $certs = [];
        $result = openssl_pkcs12_read($pfxContent, $certs, $password);

        if (! $result) {
            throw new RuntimeException(
                'No se pudo leer el archivo .pfx. Verifica la contraseña. Error: '
                    . openssl_error_string()
            );
        }

        return [
            'certificate' => $certs['cert'],   // Certificado público (.pem)
            'privateKey'  => $certs['pkey'],   // Clave privada (.pem)
        ];
    }

    /**
     * Guarda el certificado y la clave privada en disco.
     */
    public function savePem(string $pfxPath, string $password, string $outputDir): array
    {
        ['certificate' => $cert, 'privateKey' => $key] = $this->convert($pfxPath, $password);

        $certPath = rtrim($outputDir, '/') . '/certificate.pem';
        $keyPath  = rtrim($outputDir, '/') . '/private_key.pem';

        file_put_contents($certPath, $cert);
        file_put_contents($keyPath, $key);

        // Permisos restrictivos para la clave privada
        chmod($keyPath, 0600);

        return [
            'certificate_path' => $certPath,
            'private_key_path' => $keyPath,
        ];
    }
}
