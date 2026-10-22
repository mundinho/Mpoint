<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Models\Participacao;
use App\Models\Usuario;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParticipacaoController extends Controller
{
    public function __construct(private OtpService $otpService)
    {
    }

    public function resultado(Request $request, int $usuarioId): JsonResponse
    {
        /** @var Campanha|null $campanha */
        $campanha = $request->attributes->get('campanha');

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
        }

        $usuario = Usuario::find($usuarioId);
        $token = $request->header('X-Participacao-Token') ?: $request->query('token');

        // O resultado é pessoal: só o próprio participante (com o seu token) o pode ver
        if (!$usuario || !$this->otpService->tokenValido($usuario, $campanha, $token)) {
            return response()->json(['message' => 'Sem permissão para consultar este resultado.'], 403);
        }

        $participacao = Participacao::with('premio')
            ->where('campanha_id', $campanha->id)
            ->where('usuario_id', $usuarioId)
            ->first();

        if (!$participacao) {
            return response()->json(['message' => 'Este participante ainda não participou neste ciclo.'], 404);
        }

        return response()->json($participacao);
    }
}
