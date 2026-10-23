<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Services\CampanhaService;
use App\Support\CampanhaSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampanhaController extends Controller
{
    public function __construct(private CampanhaService $campanhaService)
    {
    }

    /**
     * Dados públicos da campanha do jogo (identificada pelo subdomínio). Nunca inclui
     * prémios nem números premiados — esses são só para o painel de administração.
     */
    public function ativa(Request $request): JsonResponse
    {
        /** @var Campanha|null $campanha */
        $campanha = $request->attributes->get('campanha');

        if (!$campanha) {
            return response()->json((object) []);
        }

        return response()->json([
            'id' => $campanha->id,
            'nome' => $campanha->nome,
            'slug' => $campanha->slug,
            'estado' => $campanha->estado,
            'total_quadrados' => $campanha->total_quadrados,
            'data_inicio' => $campanha->data_inicio,
            'data_fim' => $campanha->data_fim,
        ]);
    }

    /**
     * Lista todas as campanhas (activas, pausadas e encerradas), mais recente primeiro,
     * com contadores úteis para uma tela de gestão/selecção — sem carregar todos os
     * quadrados/participações de cada uma.
     */
    public function index(Request $request): JsonResponse
    {
        // Administradores com campanhas atribuídas só vêem essas
        $ids = $request->attributes->get('administrador')->campanhaIds();

        $campanhas = Campanha::when($ids !== null, fn ($query) => $query->whereIn('id', $ids))->withCount([
            'participacoes',
            'premios',
            'quadrados as quadrados_abertos_count' => fn ($query) => $query->where('estado', 'aberto'),
        ])
            ->orderByDesc('id')
            ->get();

        return response()->json($campanhas->map(fn (Campanha $campanha) => [
            'id' => $campanha->id,
            'nome' => $campanha->nome,
            'slug' => $campanha->slug,
            'link' => $campanha->link,
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
            'linhas.*.logica_aleatoriedade' => ['nullable', 'in:uniforme,aritmetica,geometrica'],
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

    /**
     * Cria uma campanha nova (em pausa) com o seu link.
     */
    public function criar(Request $request): JsonResponse
    {
        $request->merge(['slug' => CampanhaSlug::normalizar($request->input('slug'))]);

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'slug' => CampanhaSlug::regras(true),
            'total_quadrados' => ['sometimes', 'integer', 'min:1', 'max:' . config('campanhas.max_quadrados', 10000)],
            'data_inicio' => ['sometimes', 'nullable', 'date'],
            'data_fim' => ['sometimes', 'nullable', 'date', 'after_or_equal:data_inicio'],
            'otp_validade_minutos' => ['sometimes', 'integer', 'min:1', 'max:60'],
        ], CampanhaSlug::mensagens());

        $dados['slug'] = CampanhaSlug::normalizar($dados['slug']);

        return response()->json($this->campanhaService->criarCampanha($dados), 201);
    }

    /**
     * Novo ciclo da campanha: encerra-a e cria outra com a mesma configuração e link.
     */
    public function reiniciar(Campanha $campanha): JsonResponse
    {
        try {
            $nova = $this->campanhaService->reiniciar($campanha);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($nova, 201);
    }

    /**
     * Compatibilidade com o endpoint antigo: exige a campanha a reiniciar.
     */
    public function reset(Request $request): JsonResponse
    {
        $dados = $request->validate(['campanha_id' => ['required', 'integer', 'exists:campanha,id']]);

        return $this->reiniciar(Campanha::findOrFail($dados['campanha_id']));
    }

    public function atualizar(Request $request, Campanha $campanha): JsonResponse
    {
        if ($request->has('slug')) {
            $request->merge(['slug' => CampanhaSlug::normalizar($request->input('slug'))]);
        }

        $dados = $request->validate([
            'nome' => ['sometimes', 'nullable', 'string', 'max:255'],
            'slug' => CampanhaSlug::regras(false, $campanha->id),
            'data_inicio' => ['sometimes', 'nullable', 'date'],
            'data_fim' => ['sometimes', 'nullable', 'date'],
            'total_quadrados' => ['sometimes', 'integer', 'min:1'],
            'total_premios' => ['sometimes', 'integer', 'min:1'],
            'otp_validade_minutos' => ['sometimes', 'integer', 'min:1'],
            'sms_resultado_ativo' => ['sometimes', 'boolean'],
            'texto_sms_resultado' => ['sometimes', 'nullable', 'string', 'max:480'],
        ], CampanhaSlug::mensagens());

        if (isset($dados['slug'])) {
            $dados['slug'] = CampanhaSlug::normalizar($dados['slug']);
        }

        try {
            $campanha = $this->campanhaService->atualizarConfiguracoes($campanha, $dados);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($campanha);
    }

    public function activar(Campanha $campanha): JsonResponse
    {
        try {
            return response()->json($this->campanhaService->activar($campanha));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
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
