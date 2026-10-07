<?php

return [
    /*
    | Modo de demonstração (demo.mpoints.emedx.co.mz).
    | Quando activo, nenhum SMS é enviado: as mensagens são simuladas e devolvidas
    | na própria resposta da API (campo "_demo_sms") para o frontend as mostrar.
    | Desligado por omissão — a produção nunca o deve activar.
    */
    'enabled' => (bool) env('DEMO_MODE', false),

    // Administrador criado pelos dados de demonstração
    'admin_telefone' => env('DEMO_ADMIN_TELEFONE', '258840000000'),
    'admin_nome' => env('DEMO_ADMIN_NOME', 'Equipa de Demonstração'),
];
