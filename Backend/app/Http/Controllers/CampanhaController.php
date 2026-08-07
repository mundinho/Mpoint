<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Services\CampanhaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampanhaController extends Controller
{
    public function __construct(private CampanhaService $campanhaService)
    {
    }

    public function ativa(): JsonResponse
    {
        $campanha = Campanha::ativa();

        return response()->json($campanha);
    }

    public function reset(): JsonResponse
    {
        $campanha = $this->campanhaService->resetOperacional();

        return response()->json($campanha, 201);
    }

    public function definirPremios(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'premios' => ['required', 'array', 'min:1'],
            'premios.*.numero' => ['required', 'integer'],
            'premios.*.descricao' => ['required', 'string', 'max:255'],
            'premios.*.valor_estimado' => ['nullable', 'numeric'],
        ]);

        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
        }

        try {
            $campanha = $this->campanhaService->definirNumerosPremiados($campanha, $dados['premios']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($campanha->load('premios'));
    }

    public function atualizar(Request $request, Campanha $campanha): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['sometimes', 'nullable', 'string', 'max:255'],
            'data_inicio' => ['sometimes', 'nullable', 'date'],
            'data_fim' => ['sometimes', 'nullable', 'date'],
            'total_quadrados' => ['sometimes', 'integer', 'min:1'],
            'total_premios' => ['sometimes', 'integer', 'min:1'],
            'otp_validade_minutos' => ['sometimes', 'integer', 'min:1'],
        ]);

        try {
            $campanha = $this->campanhaService->atualizarConfiguracoes($campanha, $dados);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($campanha);
    }

    public function activar(Campanha $campanha): JsonResponse
    {
        return response()->json($this->campanhaService->activar($campanha));
    }

    public function pausar(Campanha $campanha): JsonResponse
    {
        return response()->json($this->campanhaService->pausar($campanha));
    }

    public function encerrar(Campanha $campanha): JsonResponse
    {
        return response()->json($this->campanhaService->encerrar($campanha));
    }
}
