<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Models\Usuario;
use App\Services\OtpService;
use App\Services\SorteioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SorteioController extends Controller
{
    public function __construct(
        private SorteioService $sorteioService,
        private OtpService $otpService
    ) {
    }

    public function abrir(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'numero' => ['required', 'integer', 'min:1'],
        ]);

        /** @var Campanha|null $campanha */
        $campanha = $request->attributes->get('campanha');

        if (!$campanha || !$campanha->estaAtiva()) {
            return response()->json(['message' => 'Esta campanha não está activa de momento.', 'codigo' => 'campanha_inactiva'], 422);
        }

        if ($dados['numero'] > $campanha->total_quadrados) {
            return response()->json(['message' => 'Número inválido.'], 422);
        }

        $usuario = Usuario::findOrFail($dados['usuario_id']);

        // Só quem validou o código SMS nesta campanha pode abrir números (impede jogar
        // em nome de outro participante conhecendo apenas o seu id)
        $token = $request->header('X-Participacao-Token') ?: $request->input('token');

        if (!$usuario->telefone_verificado || !$this->otpService->tokenValido($usuario, $campanha, $token)) {
            return response()->json(['message' => 'A sua sessão de participação expirou. Registe-se novamente.', 'codigo' => 'sessao_invalida'], 403);
        }

        try {
            $participacao = $this->sorteioService->abrirQuadrado($campanha, $usuario, $dados['numero']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($participacao, 201);
    }
}
