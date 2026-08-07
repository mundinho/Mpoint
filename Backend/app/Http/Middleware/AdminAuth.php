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

        $request->attributes->set('administrador', $administrador);

        return $next($request);
    }
}
