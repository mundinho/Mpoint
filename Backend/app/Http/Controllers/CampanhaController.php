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
            return response()->json((object) []);
        }

        return response()->json($this->formatarCampanha($campanha));
    }

    /**
     * Lista todas as campanhas (activas, pausadas e encerradas), mais recente primeiro,
     * com contadores úteis para uma tela de gestão/selecção — sem carregar todos os
     * quadrados/participações de cada uma.
     */
    public function index(): JsonResponse
    {
        $campanhas = Campanha::withCount([
            'participacoes',
            'premios',
            'quadrados as quadrados_abertos_count' => fn ($query) => $query->where('estado', 'aberto'),
        ])
            ->orderByDesc('id')
            ->get();

        return response()->json($campanhas->map(fn (Campanha $campanha) => [
            'id' => $campanha->id,
            'nome' => $campanha->nome,
            'estado' => $campanha->estado,
            'modo_distribuicao' => $campanha->modo_distribuicao,
            'data_inicio' => $campanha->data_inicio,
            'data_fim' => $campanha->data_fim,
            'total_quadrados' => $campanha->total_quadrados,
            'total_premios' => $campanha->total_premios,
            'quadrados_abertos' => $campanha->quadrados_abertos_count,
            'premios_configurados' => $campanha->premios_count,
            'participantes' => $campanha->participacoes_count,
        ]));
    }

    /**
     * Detalhe completo de uma campanha específica (qualquer estado), pelo id — mesmo
     * formato de `ativa()`, mas permite seleccionar e gerir ciclos anteriores/encerrados.
     */
    public function mostrar(Campanha $campanha): JsonResponse
    {
        return response()->json($this->formatarCampanha($campanha));
    }

    public function distribuicaoAleatoria(Request $request, Campanha $campanha): JsonResponse
    {
        $dados = $request->validate([
            'linhas' => ['required', 'array', 'min:1'],
            'linhas.*.nome' => ['required', 'string', 'max:255'],
            'linhas.*.quantidade' => ['required', 'integer', 'min:1'],
            'linhas.*.logica_aleatoriedade' => ['nullable', 'string', 'max:100'],
            'linhas.*.data_programada' => ['nullable', 'date'],
        ]);

        try {
            $campanha = $this->campanhaService->configurarDistribuicaoAleatoria($campanha, $dados['linhas']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->formatarCampanha($campanha));
    }

    public function distribuicaoManual(Request $request, Campanha $campanha): JsonResponse
    {
        $dados = $request->validate([
            'premios' => ['required', 'array', 'min:1'],
            'premios.*.numero' => ['required', 'integer'],
            'premios.*.nome' => ['required', 'string', 'max:255'],
            'premios.*.data_programada' => ['nullable', 'date'],
        ]);

        try {
            $campanha = $this->campanhaService->configurarDistribuicaoManual($campanha, $dados['premios']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->formatarCampanha($campanha));
    }

    private function formatarCampanha(Campanha $campanha): array
    {
        $premios = $campanha->premios()->with('quadrado')->get()->map(fn ($premio) => [
            'id' => $premio->id,
            'numero' => $premio->quadrado?->numero,
            'nome' => $premio->nome,
            'data_programada' => $premio->data_programada,
            'entregue' => $premio->entregue,
        ]);

        $distribuicaoAleatoria = $campanha->distribuicaoAleatoria()->get()->map(fn ($linha) => [
            'nome' => $linha->nome,
            'quantidade' => $linha->quantidade,
            'logica_aleatoriedade' => $linha->logica_aleatoriedade,
            'data_programada' => $linha->data_programada,
        ]);

        return array_merge($campanha->toArray(), [
            'premios' => $premios,
            'distribuicao_aleatoria' => $distribuicaoAleatoria,
        ]);
    }

    public function reset(): JsonResponse
    {
        $campanha = $this->campanhaService->resetOperacional();

        return response()->json($campanha, 201);
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
