<?php

namespace App\Http\Middleware;

use App\Models\Campanha;
use App\Support\CampanhaSlug;
use Closure;
use Illuminate\Http\Request;

/**
 * Rotas públicas do jogo: identifica a campanha pelo cabeçalho X-Campanha, que o
 * frontend preenche com o subdomínio (bci.c.mpoints.site → "bci").
 * Sem cabeçalho (domínio antigo), usa a campanha activa mais recente.
 * A campanha resolvida fica em $request->attributes->get('campanha') (pode ser null).
 */
class ResolverCampanhaPublica
{
    public function handle(Request $request, Closure $next)
    {
        $cabecalho = $request->header('X-Campanha');

        if ($cabecalho !== null && $cabecalho !== '') {
            $slug = CampanhaSlug::normalizar($cabecalho);

            // Valida antes de consultar a base de dados
            $campanha = CampanhaSlug::valido($slug)
                ? Campanha::where('slug', $slug)->first()
                : null;

            if (!$campanha) {
                return response()->json(['message' => 'Campanha não encontrada.', 'codigo' => 'campanha_inexistente'], 404);
            }
        } else {
            $campanha = Campanha::ativa();
        }

        $request->attributes->set('campanha', $campanha);

        return $next($request);
    }
}
