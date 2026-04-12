<?php

namespace App\Infrastructure\Sunat\Services;

use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;

class GreenterService
{
    /**
     * Obtiene la instancia configurada de See.
     */
    public function getSee(): See
    {
        $see = new See();

        // Configuración de Credenciales
        $config = config('facturacion');

        $see->setCredentials(
            $config['ruc'] . ($config['user'] ? $config['user'] : 'MODODATOS'),
            $config['password'] ? $config['password'] : 'MODODATOS'
        );

        // Certificado y Llave Privada (Greenter necesita ambos para firmar)
        //  $cert = file_get_contents(storage_path('cert.pem'));
        // $key  = file_get_contents(storage_path('key.pem'));


        $cert = file_get_contents(storage_path('app/certificado/convertido/cert.pem'));
        $key  = file_get_contents(storage_path('app/certificado/convertido/key.pem'));




        $see->setCertificate($cert . "\n" . $key);

        // SUNAT endpoint (beta o prod)
        // $endpoint = ($config['modo'] === 'produccion')
        //     ? SunatEndpoints::FE_PRODUCCION
        //     : SunatEndpoints::FE_BETA;


        $endpoint = 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService?wsdl';

        $see->setService($endpoint);

        return $see;
    }
}
