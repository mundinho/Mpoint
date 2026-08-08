<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Services\CampanhaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PremioController extends Controller
{
    public function __construct(private CampanhaService $campanhaService)
    {
    }

    public function index(): JsonResponse
    {
        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
        }

        $quadrados = $campanha->quadrados()
            ->whereNotNull('premio_id')
            ->with('premio')
            ->orderBy('numero')
            ->get();

        return response()->json($quadrados->map(fn ($q) => [
            'numero' => $q->numero,
            'estado' => $q->estado,
            'premio_id' => $q->premio->id,
            'descricao' => $q->premio->descricao,
            'valor_estimado' => $q->premio->valor_estimado,
            'data_programada' => $q->premio->data_programada,
            'entregue' => $q->premio->entregue,
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'numero' => ['required', 'integer', 'min:1'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor_estimado' => ['nullable', 'numeric'],
            'data_programada' => ['nullable', 'date'],
        ]);

        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
        }

        try {
            $premio = $this->campanhaService->associarPremio(
                $campanha,
                $dados['numero'],
                $dados['descricao'],
                $dados['valor_estimado'] ?? null,
                $dados['data_programada'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($premio, 201);
    }

    public function update(Request $request, int $numero): JsonResponse
    {
        $dados = $request->validate([
            'descricao' => ['sometimes', 'string', 'max:255'],
            'valor_estimado' => ['sometimes', 'nullable', 'numeric'],
            'data_programada' => ['sometimes', 'nullable', 'date'],
            'entregue' => ['sometimes', 'boolean'],
        ]);

        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
        }

        try {
            $premio = $this->campanhaService->editarPremio($campanha, $numero, $dados);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($premio);
    }

    public function destroy(int $numero): JsonResponse
    {
        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
        }

        try {
            $this->campanhaService->removerPremio($campanha, $numero);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(null, 204);
    }
}
