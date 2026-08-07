<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Models\Participacao;
use Illuminate\Http\JsonResponse;

class ParticipacaoController extends Controller
{
    public function resultado(int $usuarioId): JsonResponse
    {
        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
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
