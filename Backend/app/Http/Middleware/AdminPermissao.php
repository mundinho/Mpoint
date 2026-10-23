<?php

namespace App\Http\Middleware;

use App\Models\Administrador;
use App\Models\Campanha;
use Closure;
use Illuminate\Http\Request;

/**
 * Permissões do painel (depois do admin.auth):
 *  - admin.permissao:campanha → a campanha da rota ({campanha}) ou do pedido (campanha_id)
 *    tem de estar entre as que o administrador gere;
 *  - admin.permissao:total    → acesso a todas as campanhas;
 *  - admin.permissao:master   → administrador master.
 */
class AdminPermissao
{
    public function handle(Request $request, Closure $next, string $nivel)
    {
        /** @var Administrador|null $administrador */
        $administrador = $request->attributes->get('administrador');

        if (!$administrador) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        $permitido = match ($nivel) {
            'master' => $administrador->master,
            'total' => $administrador->acessoTotal(),
            'campanha' => $this->podeGerirCampanhaDoPedido($request, $administrador),
            default => false,
        };

        if (!$permitido) {
            return response()->json([
                'message' => 'Não tem permissão para esta acção.',
                'codigo' => 'sem_permissao',
            ], 403);
        }

        return $next($request);
    }

    private function podeGerirCampanhaDoPedido(Request $request, Administrador $administrador): bool
    {
        $campanha = $request->route('campanha') ?? $request->input('campanha_id');

        if ($campanha instanceof Campanha) {
            return $administrador->podeGerir($campanha);
        }

        if (!is_numeric($campanha)) {
            // Sem campanha identificável: só quem gere todas
            return $administrador->acessoTotal();
        }

        return $administrador->podeGerir((int) $campanha);
    }
}
