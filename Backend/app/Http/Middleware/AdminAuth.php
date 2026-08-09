<?php

namespace App\Http\Middleware;

use App\Models\Administrador;
use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        $administrador = Administrador::where('api_token', hash('sha256', $token))->where('ativo', true)->first();

        if (!$administrador) {
            return response()->json(['message' => 'Token inválido.'], 401);
        }

        if (!$administrador->token_expira_em || $administrador->token_expira_em->isPast()) {
            $administrador->update(['api_token' => null, 'token_expira_em' => null]);

            return response()->json(['message' => 'Sessão expirada, inicie sessão novamente.'], 401);
        }

        // Sessão desliza: cada pedido autenticado renova a janela de inactividade.
        $administrador->update(['token_expira_em' => now()->addMinutes(Administrador::SESSION_MINUTES)]);

        $request->attributes->set('administrador', $administrador);

        return $next($request);
    }
}
