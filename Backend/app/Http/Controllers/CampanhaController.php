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

        if (!$campanha) {
            return response()->json(null);
        }

        $dados = $campanha->toArray();

        if ($campanha->modo_distribuicao === 'aleatorio') {
            $dados['distribuicao_aleatoria'] = $campanha->distribuicaoAleatoriaConfig()
                ->with('categoria')
                ->get()
                ->map(fn ($linha) => [
                    'categoria_id' => $linha->categoria_id,
                    'categoria_nome' => $linha->categoria->nome,
                    'quantidade' => $linha->quantidade,
                    'data_programada' => $linha->data_programada,
                ]);
            $dados['premios'] = null;
        } else {
            $dados['distribuicao_aleatoria'] = null;
            $dados['premios'] = $campanha->quadrados()
                ->whereNotNull('premio_id')
                ->with(['premio.categoria'])
                ->orderBy('numero')
                ->get()
                ->map(fn ($q) => [
                    'numero' => $q->numero,
                    'categoria_id' => $q->premio->categoria_id,
                    'categoria_nome' => $q->premio->categoria?->nome,
                    'descricao' => $q->premio->descricao,
                    'data_programada' => $q->premio->data_programada,
                    'entregue' => $q->premio->entregue,
                ]);
        }

        return response()->json($dados);
    }

    public function configurarDistribuicaoManual(Request $request, Campanha $campanha): JsonResponse
    {
        $dados = $request->validate([
            'premios' => ['required', 'array', 'min:1'],
            'premios.*.numero' => ['required', 'integer'],
            'premios.*.categoria_id' => ['required', 'integer', 'exists:categoria_premio,id'],
            'premios.*.descricao' => ['required', 'string', 'max:255'],
            'premios.*.data_programada' => ['nullable', 'date'],
        ]);

        try {
            $campanha = $this->campanhaService->configurarDistribuicaoManual($campanha, $dados['premios']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($campanha);
    }

    public function configurarDistribuicaoAleatoria(Request $request, Campanha $campanha): JsonResponse
    {
        $dados = $request->validate([
            'linhas' => ['required', 'array', 'min:1'],
            'linhas.*.categoria_id' => ['required', 'integer', 'exists:categoria_premio,id'],
            'linhas.*.quantidade' => ['required', 'integer', 'min:1'],
            'linhas.*.data_programada' => ['nullable', 'date'],
        ]);

        try {
            $campanha = $this->campanhaService->configurarDistribuicaoAleatoria($campanha, $dados['linhas']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

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
