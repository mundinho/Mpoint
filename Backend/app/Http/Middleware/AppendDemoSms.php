<?php

namespace App\Http\Middleware;

use App\Support\DemoSms;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Modo de demonstração: acrescenta à resposta JSON os SMS que teriam sido
 * enviados durante o pedido, para o frontend os mostrar no ecrã.
 */
class AppendDemoSms
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!config('demo.enabled')) {
            return $response;
        }

        $mensagens = DemoSms::retirar();

        if ($mensagens && $response instanceof JsonResponse) {
            $dados = $response->getData(true);

            if (is_array($dados) && Arr::isAssoc($dados)) {
                $dados['_demo_sms'] = $mensagens;
                $response->setData($dados);
            }
        }

        return $response;
    }
}
