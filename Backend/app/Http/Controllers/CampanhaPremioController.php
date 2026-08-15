<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Models\CampanhaPremio;
use App\Models\PremioBanco;
use App\Services\CampanhaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampanhaPremioController extends Controller
{
    public function __construct(private CampanhaService $campanhaService)
    {
    }

    public function index(Campanha $campanha): JsonResponse
    {
        $itens = $campanha->campanhaPremios()
            ->with(['premioBanco', 'premios.quadrado'])
            ->orderBy('id')
            ->get();

        return response()->json($itens->map(fn (CampanhaPremio $item) => $this->formatar($item)));
    }

    public function store(Request $request, Campanha $campanha): JsonResponse
    {
        $dados = $request->validate([
            'premio_banco_id' => ['required', 'integer', 'exists:premios_banco,id'],
            'modo_distribuicao' => ['required', 'in:manual,aleatorio'],
            'numero' => ['required_if:modo_distribuicao,manual', 'integer'],
            'quantidade' => ['required_if:modo_distribuicao,aleatorio', 'integer', 'min:1'],
            'logica_aleatoriedade' => ['nullable', 'in:uniforme,aritmetica,geometrica'],
            'data_programada' => ['nullable', 'date'],
        ]);

        $premioBanco = PremioBanco::findOrFail($dados['premio_banco_id']);

        try {
            $campanhaPremio = $this->campanhaService->adicionarPremioCampanha(
                $campanha,
                $premioBanco,
                $dados['modo_distribuicao'],
                $dados
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $campanhaPremio->load(['premioBanco', 'premios.quadrado']);

        return response()->json($this->formatar($campanhaPremio), 201);
    }

    public function update(Request $request, Campanha $campanha, CampanhaPremio $campanhaPremio): JsonResponse
    {
        $dados = $request->validate([
            'numero' => ['sometimes', 'integer'],
            'quantidade' => ['sometimes', 'integer', 'min:1'],
            'data_programada' => ['sometimes', 'nullable', 'date'],
        ]);

        try {
            $campanhaPremio = $this->campanhaService->editarPremioCampanha($campanhaPremio, $dados);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $campanhaPremio->load(['premioBanco', 'premios.quadrado']);

        return response()->json($this->formatar($campanhaPremio));
    }

    public function destroy(Campanha $campanha, CampanhaPremio $campanhaPremio): JsonResponse
    {
        try {
            $this->campanhaService->removerPremioCampanha($campanhaPremio);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(null, 204);
    }

    private function formatar(CampanhaPremio $item): array
    {
        $premios = $item->premios;

        return [
            'id' => $item->id,
            'premio_banco_id' => $item->premio_banco_id,
            'nome' => $item->premioBanco->nome,
            'descricao' => $item->premioBanco->descricao,
            'modo_distribuicao' => $item->modo_distribuicao,
            'numero' => $item->modo_distribuicao === 'manual' ? $premios->first()?->quadrado?->numero : null,
            'quantidade' => $item->quantidade,
            'logica_aleatoriedade' => $item->logica_aleatoriedade,
            'data_programada' => $item->data_programada,
            'numeros' => $premios->map(fn ($p) => $p->quadrado?->numero)->filter()->values(),
            'quantidade_atribuida' => $premios->filter(fn ($p) => $p->quadrado && $p->quadrado->estado !== 'disponivel')->count(),
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }
}
