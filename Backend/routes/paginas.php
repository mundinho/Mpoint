<?php

use App\Http\Controllers\PaginaCampanhaController;
use Illuminate\Support\Facades\Route;

/*
| Domínio de cada campanha ({link}.c.mpoints.site): página do jogo com as etiquetas de
| partilha (WhatsApp, Facebook…) e a imagem de pré-visualização. Só responde nos
| domínios de campanha; os outros domínios não são afectados.
*/

$sufixo = config('campanhas.host_suffix');

if ($sufixo) {
    Route::domain('{subdominio}.' . $sufixo)
        ->where(['subdominio' => '[A-Za-z0-9.-]+'])
        ->group(function () {
            Route::get('og-imagem.png', [PaginaCampanhaController::class, 'imagem']);
            Route::get('{caminho?}', [PaginaCampanhaController::class, 'pagina'])->where('caminho', '.*');
        });
}
