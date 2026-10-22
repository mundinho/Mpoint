<?php

return [
    /*
    | Sufixo dos subdomínios das campanhas: cada campanha fica acessível em
    | https://{slug}.{host_suffix} — ex.: bci.c.mpoints.site
    | Produção: c.mpoints.site · Demonstração: c.demo.mpoints.site
    */
    'host_suffix' => env('CAMPANHAS_HOST_SUFFIX'),

    // Limites do total de números de uma campanha nova
    'max_quadrados' => (int) env('CAMPANHAS_MAX_QUADRADOS', 10000),

    // Validade do token de participação emitido após validar o OTP
    'token_minutos' => (int) env('CAMPANHAS_TOKEN_MINUTOS', 30),
];
