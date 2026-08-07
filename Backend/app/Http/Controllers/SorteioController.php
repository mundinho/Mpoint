<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Models\Usuario;
use App\Services\SorteioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SorteioController extends Controller
{
    public function __construct(private SorteioService $sorteioService)
    {
    }

    public function abrir(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'numero' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
        }

        $usuario = Usuario::findOrFail($dados['usuario_id']);

        if (!$usuario->telefone_verificado) {
            return response()->json(['message' => 'Telefone ainda não validado.'], 422);
        }

        try {
            $participacao = $this->sorteioService->abrirQuadrado($campanha, $usuario, $dados['numero']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($participacao, 201);
    }
}
