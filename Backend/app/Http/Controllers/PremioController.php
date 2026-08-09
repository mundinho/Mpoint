<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Models\Premio;
use App\Services\CampanhaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PremioController extends Controller
{
    public function __construct(private CampanhaService $campanhaService)
    {
    }

    public function resumo(Campanha $campanha): JsonResponse
    {
        $premios = Premio::where('campanha_id', $campanha->id)->with('quadrado')->get();

        $resumo = $premios->groupBy('nome')->map(function ($grupo, $nome) {
            $total = $grupo->count();
            $atribuida = $grupo->filter(fn (Premio $premio) => $premio->quadrado && $premio->quadrado->estado !== 'disponivel')->count();

            return [
                'id' => $grupo->min('id'),
                'nome' => $nome,
                'quantidade_total' => $total,
                'quantidade_atribuida' => $atribuida,
                'quantidade_remanescente' => $total - $atribuida,
            ];
        })->values();

        return response()->json($resumo);
    }

    public function update(Request $request, Campanha $campanha, int $numero): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['sometimes', 'string', 'max:255'],
            'data_programada' => ['sometimes', 'nullable', 'date'],
            'entregue' => ['sometimes', 'boolean'],
        ]);

        try {
            $premio = $this->campanhaService->editarPremio($campanha, $numero, $dados);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($premio);
    }
}
