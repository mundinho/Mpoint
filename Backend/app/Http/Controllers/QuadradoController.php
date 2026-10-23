<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuadradoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $campanha = $this->campanhaActiva($request);

        if ($campanha instanceof JsonResponse) {
            return $campanha;
        }

        // Só número e estado; "premiado" apenas para números já abertos —
        // nunca expor que números ainda por abrir têm prémio
        $quadrados = $campanha->quadrados()
            ->orderBy('numero')
            ->get(['id', 'numero', 'estado', 'premio_id'])
            ->map(fn ($quadrado) => [
                'id' => $quadrado->id,
                'numero' => $quadrado->numero,
                'estado' => $quadrado->estado,
                'premiado' => $quadrado->estado === 'aberto' && $quadrado->premio_id !== null,
            ]);

        return response()->json($quadrados);
    }

    /**
     * Contadores do jogo, consultados periodicamente pelo ecrã do sorteio.
     * Os vencedores contam assim que o número é aberto, com o prémio entregue ou não.
     */
    public function resumo(Request $request): JsonResponse
    {
        $campanha = $this->campanhaActiva($request);

        if ($campanha instanceof JsonResponse) {
            return $campanha;
        }

        $abertos = $campanha->quadrados()->where('estado', 'aberto')->count();

        return response()->json([
            'total' => (int) $campanha->quadrados()->count(),
            'abertos' => $abertos,
            'vencedores' => $campanha->participacoes()->where('resultado', 'vencedor')->count(),
        ]);
    }

    private function campanhaActiva(Request $request): Campanha|JsonResponse
    {
        /** @var Campanha|null $campanha */
        $campanha = $request->attributes->get('campanha');

        if (!$campanha || !$campanha->estaAtiva()) {
            return response()->json(['message' => 'Esta campanha não está activa de momento.', 'codigo' => 'campanha_inactiva'], 422);
        }

        return $campanha;
    }
}
