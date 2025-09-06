<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Define quais proxies o Laravel deve confiar.
    | - null → nenhum proxy confiado (padrão, mais seguro).
    | - lista de IPs ou subnets (ex: ["192.168.0.1", "10.0.0.0/8"]).
    |
    */

    'trusted_ips' => env('TRUSTED_PROXIES') ? array_map('trim', explode(',', env('TRUSTED_PROXIES'))) : null,
];
