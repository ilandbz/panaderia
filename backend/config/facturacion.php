<?php

return [
    'ruc'            => env('SUNAT_RUC', '20612095061'),
    'razon_social'   => env('SUNAT_RAZON_SOCIAL', 'M & W JARA S.A.C'),
    'direccion'      => env('SUNAT_DIRECCION', 'Av. Próceres de la Independencia 123, SJL, Lima'),
    'user'           => env('SUNAT_USER'),
    'password'       => env('SUNAT_PASSWORD'),
    'modo'           => env('SUNAT_MODO', 'beta'), // beta | produccion
    'storage_paths'  => [
        'xml' => 'sunat/xml',
        'cdr' => 'sunat/cdr',
    ],
];
