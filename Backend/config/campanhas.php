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

    // HTML do jogo (build do frontend), servido com as etiquetas de partilha de cada campanha
    'spa_index' => env('CAMPANHAS_SPA_INDEX', base_path('../Frontend/dist/index.html')),

    // Imagem de partilha (Open Graph): Python com Pillow e fontes TrueType
    'og_python' => env('CAMPANHAS_OG_PYTHON', 'python3'),
    'og_fonte_bold' => env('CAMPANHAS_OG_FONTE_BOLD', '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf'),
    'og_fonte' => env('CAMPANHAS_OG_FONTE', '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf'),
];
