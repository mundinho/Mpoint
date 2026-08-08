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
<<<<<<< HEAD
            return response()->json(null);
        }

        $dados = $campanha->toArray();

        if ($campanha->modo_distribuicao === 'aleatorio') {
            $dados['premios'] = $campanha->premios()
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'nome' => $p->nome,
                    'quantidade' => $p->quantidade,
                    'logica_aleatoriedade' => $p->logica_aleatoriedade,
                    'data_programada' => $p->data_programada,
                    'especial' => $p->especial,
                ]);
        } else {
            $dados['premios'] = $campanha->quadrados()
                ->whereNotNull('premio_id')
                ->with('premio')
                ->orderBy('numero')
                ->get()
                ->map(fn ($q) => [
                    'numero' => $q->numero,
                    'premio_id' => $q->premio->id,
                    'nome' => $q->premio->nome,
                    'data_programada' => $q->premio->data_programada,
                    'especial' => $q->premio->especial,
                    'estado' => $q->estado,
                    'entregue' => $q->premio->entregue,
                ]);
        }

        return response()->json($dados);
    }

    public function configurarDistribuicaoManual(Request $request, Campanha $campanha): JsonResponse
=======
            return response()->json((object) []);
        }

        return response()->json($this->formatarCampanha($campanha));
    }

    public function distribuicaoAleatoria(Request $request, Campanha $campanha): JsonResponse
    {
        $dados = $request->validate([
            'linhas' => ['required', 'array', 'min:1'],
            'linhas.*.categoria_id' => ['required', 'integer', 'exists:categorias_premio,id'],
            'linhas.*.quantidade' => ['required', 'integer', 'min:1'],
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
>>>>>>> 318b0efbcc92ff9a31ee160f7e2209f82ee66809
    {
        $dados = $request->validate([
            'premios' => ['required', 'array', 'min:1'],
            'premios.*.numero' => ['required', 'integer'],
<<<<<<< HEAD
            'premios.*.nome' => ['required', 'string', 'max:255'],
            'premios.*.data_programada' => ['nullable', 'date'],
            'premios.*.especial' => ['nullable', 'in:normal,tentar_novamente'],
=======
            'premios.*.categoria_id' => ['required', 'integer', 'exists:categorias_premio,id'],
            'premios.*.descricao' => ['required', 'string', 'max:255'],
            'premios.*.data_programada' => ['nullable', 'date'],
>>>>>>> 318b0efbcc92ff9a31ee160f7e2209f82ee66809
        ]);

        try {
            $campanha = $this->campanhaService->configurarDistribuicaoManual($campanha, $dados['premios']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

<<<<<<< HEAD
        return response()->json($campanha);
    }

    public function configurarDistribuicaoAleatoria(Request $request, Campanha $campanha): JsonResponse
    {
        $dados = $request->validate([
            'premios' => ['required', 'array', 'min:1'],
            'premios.*.nome' => ['required', 'string', 'max:255'],
            'premios.*.quantidade' => ['required', 'integer', 'min:1'],
            'premios.*.data_programada' => ['nullable', 'date'],
            'premios.*.logica_aleatoriedade' => ['nullable', 'string'],
            'premios.*.especial' => ['nullable', 'in:normal,tentar_novamente'],
        ]);

        try {
            $campanha = $this->campanhaService->configurarDistribuicaoAleatoria($campanha, $dados['premios']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($campanha);
=======
        return response()->json($this->formatarCampanha($campanha));
    }

    private function formatarCampanha(Campanha $campanha): array
    {
        $premios = $campanha->premios()->with(['categoria', 'quadrado'])->get()->map(fn ($premio) => [
            'id' => $premio->id,
            'numero' => $premio->quadrado?->numero,
            'categoria_id' => $premio->categoria_id,
            'categoria_nome' => $premio->categoria?->nome,
            'descricao' => $premio->descricao,
            'data_programada' => $premio->data_programada,
            'entregue' => $premio->entregue,
        ]);

        $distribuicaoAleatoria = $campanha->distribuicaoAleatoria()->get()->map(fn ($linha) => [
            'categoria_id' => $linha->categoria_id,
            'quantidade' => $linha->quantidade,
            'data_programada' => $linha->data_programada,
        ]);

        return array_merge($campanha->toArray(), [
            'premios' => $premios,
            'distribuicao_aleatoria' => $distribuicaoAleatoria,
        ]);
>>>>>>> 318b0efbcc92ff9a31ee160f7e2209f82ee66809
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
            'premios.*.nome' => ['required', 'string', 'max:255'],
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
