<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Models\Otp;
use App\Models\Participacao;
use App\Models\Premio;
use App\Models\Sms;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminDashboardController extends Controller
{
    public function __construct(private AuditoriaService $auditoria)
    {
    }

    public function estatisticas(Campanha $campanha): JsonResponse
    {
        return response()->json([
            'total_participantes' => Usuario::count(),
            'participantes_validados' => Usuario::where('telefone_verificado', true)->count(),
            'participantes_pendentes' => Usuario::where('telefone_verificado', false)->count(),
            'total_numeros' => $campanha->total_quadrados,
            'numeros_disponiveis' => $campanha->quadrados()->where('estado', 'disponivel')->count(),
            'numeros_abertos' => $campanha->quadrados()->where('estado', 'aberto')->count(),
            'premios_disponiveis' => $campanha->premios()->where('entregue', false)->count(),
            'premios_entregues' => $campanha->premios()->where('entregue', true)->count(),
        ]);
    }

    /**
     * Dados agregados prontos a consumir por gráficos no frontend (linhas temporais,
     * distribuições e funil de participação), relativos à campanha indicada na rota
     * — excepto os blocos explicitamente globais (registos e SMS).
     */
    public function relatorios(Campanha $campanha): JsonResponse
    {
        $totalRegistados = Usuario::count();
        $totalValidados = Usuario::where('telefone_verificado', true)->count();
        $totalJogaram = Participacao::where('campanha_id', $campanha->id)->count();
        $totalVenceram = Participacao::where('campanha_id', $campanha->id)->where('resultado', 'vencedor')->count();
        $totalNaoVenceram = Participacao::where('campanha_id', $campanha->id)->where('resultado', 'nao_vencedor')->count();
        $totalPendentes = Participacao::where('campanha_id', $campanha->id)->where('resultado', 'pendente')->count();

        return response()->json([
            'resumo' => [
                'total_quadrados' => $campanha->total_quadrados,
                'total_registados' => $totalRegistados,
                'total_validados' => $totalValidados,
                'total_pendentes_validacao' => $totalRegistados - $totalValidados,
                'total_jogaram' => $totalJogaram,
                'total_venceram' => $totalVenceram,
                'total_nao_venceram' => $totalNaoVenceram,
                'total_pendentes_resultado' => $totalPendentes,
            ],

            // Séries temporais (uma linha por hora), boas para gráficos de linha/barras.
            'jogadas_por_hora' => $this->porHora(
                Participacao::where('campanha_id', $campanha->id)
            ),

            'vencedores_por_hora' => $this->porHora(
                Participacao::where('campanha_id', $campanha->id)->where('resultado', 'vencedor')
            ),

            'premios_atribuidos_por_hora' => $this->porHora(
                Participacao::where('campanha_id', $campanha->id)->whereNotNull('premio_id')
            ),

            'registos_por_hora' => $this->porHora(Usuario::query()),

            // Distribuições, boas para gráficos de pizza/barras.
            'resultados' => Participacao::where('campanha_id', $campanha->id)
                ->selectRaw('resultado, COUNT(*) as quantidade')
                ->groupBy('resultado')
                ->get()
                ->map(fn ($linha) => [
                    'resultado' => $linha->resultado,
                    'quantidade' => (int) $linha->quantidade,
                ]),

            'premios_por_nome' => Premio::where('campanha_id', $campanha->id)
                ->selectRaw('nome, COUNT(*) as quantidade')
                ->groupBy('nome')
                ->orderByDesc('quantidade')
                ->get()
                ->map(fn ($linha) => [
                    'nome' => $linha->nome,
                    'quantidade' => (int) $linha->quantidade,
                ]),

            'numeros_por_estado' => $campanha->quadrados()
                ->selectRaw('estado, COUNT(*) as quantidade')
                ->groupBy('estado')
                ->get()
                ->map(fn ($linha) => [
                    'estado' => $linha->estado,
                    'quantidade' => (int) $linha->quantidade,
                ]),

            // SMS é uma tabela global (não tem campanha_id) — reflecte toda a operação, não só este ciclo.
            'sms_por_tipo_e_estado' => Sms::selectRaw('tipo, estado, COUNT(*) as quantidade')
                ->groupBy('tipo', 'estado')
                ->get()
                ->map(fn ($linha) => [
                    'tipo' => $linha->tipo,
                    'estado' => $linha->estado,
                    'quantidade' => (int) $linha->quantidade,
                ]),

            // Funil de conversão, bom para gráfico de funil/barras horizontais.
            'funil' => [
                ['etapa' => 'Registados', 'quantidade' => $totalRegistados],
                ['etapa' => 'Telefone validado', 'quantidade' => $totalValidados],
                ['etapa' => 'Jogaram', 'quantidade' => $totalJogaram],
                ['etapa' => 'Venceram', 'quantidade' => $totalVenceram],
            ],
        ]);
    }

    /**
     * Agrupa os resultados de uma query por hora (baseado em created_at), devolvendo
     * uma série ordenada de {hora, quantidade} pronta para um gráfico de linha/barras.
     */
    private function porHora(Builder $query, string $coluna = 'created_at'): Collection
    {
        return $query
            ->selectRaw("DATE_FORMAT({$coluna}, '%Y-%m-%d %H:00:00') as hora, COUNT(*) as quantidade")
            ->groupBy('hora')
            ->orderBy('hora')
            ->get()
            ->map(fn ($linha) => [
                'hora' => $linha->hora,
                'quantidade' => (int) $linha->quantidade,
            ]);
    }

    public function participantes(Campanha $campanha): JsonResponse
    {
        $usuarios = Usuario::with(['participacoes' => function ($q) use ($campanha) {
            $q->where('campanha_id', $campanha->id)->with('premio')->latest('id');
        }])->get();

        return response()->json($usuarios->map(function (Usuario $usuario) {
            $participacao = $usuario->participacoes->first();
            $tentativasUsadas = $usuario->participacoes->count();
            $tentativasDisponiveis = max(0, (1 + $usuario->tentativas_extra) - $tentativasUsadas);

            return [
                'id' => $usuario->id,
                'nome' => $usuario->nome,
                'telefone' => $usuario->telefone,
                'estado' => $usuario->telefone_verificado ? 'validado' : 'pendente',
                'numero' => $participacao->numero ?? null,
                'resultado' => $participacao->resultado ?? null,
                'premio' => $participacao?->premio?->nome,
                'participou_em' => $participacao->created_at ?? null,
                'tentativas_usadas' => $tentativasUsadas,
                'tentativas_disponiveis' => $tentativasDisponiveis,
            ];
        }));
    }

    public function concederTentativa(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
        ]);

        $usuario = Usuario::findOrFail($dados['usuario_id']);
        $usuario->increment('tentativas_extra');

        $this->auditoria->registrar('Usuario', 'conceder_tentativa', true, "Tentativa extra concedida ao usuario {$usuario->id} ({$usuario->nome}).");

        return response()->json($usuario->fresh());
    }

    public function atividade(Campanha $campanha): JsonResponse
    {
        $registos = Usuario::orderByDesc('created_at')->limit(10)->get()->map(fn (Usuario $usuario) => [
            'tipo' => 'registo',
            'usuario_id' => $usuario->id,
            'nome' => $usuario->nome,
            'numero' => null,
            'premio' => null,
            'data_hora' => $usuario->created_at,
        ]);

        $validacoes = Otp::whereNotNull('validado_em')
            ->with('usuario')
            ->orderByDesc('validado_em')
            ->limit(10)
            ->get()
            ->filter(fn (Otp $otp) => $otp->usuario !== null)
            ->map(fn (Otp $otp) => [
                'tipo' => 'validacao',
                'usuario_id' => $otp->usuario->id,
                'nome' => $otp->usuario->nome,
                'numero' => null,
                'premio' => null,
                'data_hora' => $otp->validado_em,
            ]);

        $participacoes = Participacao::with(['usuario', 'premio'])
            ->where('campanha_id', $campanha->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (Participacao $p) => [
                'tipo' => $p->resultado === 'pendente' ? 'participacao' : $p->resultado,
                'usuario_id' => $p->usuario_id,
                'nome' => $p->usuario->nome,
                'numero' => $p->numero,
                'premio' => $p->premio?->nome,
                'data_hora' => $p->created_at,
            ]);

        $entregas = Premio::where('campanha_id', $campanha->id)
            ->where('entregue', true)
            ->with('quadrado.abertoPor')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->filter(fn (Premio $premio) => $premio->quadrado?->abertoPor !== null)
            ->map(fn (Premio $premio) => [
                'tipo' => 'premio_entregue',
                'usuario_id' => $premio->quadrado->abertoPor->id,
                'nome' => $premio->quadrado->abertoPor->nome,
                'numero' => $premio->quadrado->numero,
                'premio' => $premio->nome,
                'data_hora' => $premio->updated_at,
            ]);

        $atividade = $registos->concat($validacoes)->concat($participacoes)->concat($entregas)
            ->sortByDesc('data_hora')
            ->values()
            ->take(20);

        return response()->json($atividade);
    }

    public function vencedores(Campanha $campanha): JsonResponse
    {
        $vencedores = Participacao::with(['usuario', 'premio'])
            ->where('campanha_id', $campanha->id)
            ->where('resultado', 'vencedor')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($vencedores->map(fn (Participacao $p) => [
            'participacao_id' => $p->id,
            'usuario_id' => $p->usuario_id,
            'nome' => $p->usuario->nome,
            'telefone' => $p->usuario->telefone,
            'numero' => $p->numero,
            'premio' => $p->premio?->nome,
            'entrega_estado' => $p->premio?->entregue ? 'entregue' : 'pendente',
            'data_hora' => $p->created_at,
        ]));
    }
}
