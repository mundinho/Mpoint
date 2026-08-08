<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Campanha;
use App\Models\Participacao;
use App\Models\ParticipanteCampanha;
use App\Models\Premio;
use App\Models\Usuario;
use App\Services\CampanhaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(private CampanhaService $campanhaService)
    {
    }

    public function estatisticas(): JsonResponse
    {
        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
        }

        return response()->json([
            'total_participantes' => Usuario::count(),
            'participantes_validados' => Usuario::where('telefone_verificado', true)->count(),
            'participantes_pendentes' => Usuario::where('telefone_verificado', false)->count(),
            'numeros_disponiveis' => $campanha->quadrados()->where('estado', 'disponivel')->count(),
            'numeros_abertos' => $campanha->quadrados()->where('estado', 'aberto')->count(),
            'premios_disponiveis' => $campanha->premios()->where('entregue', false)->count(),
            'premios_entregues' => $campanha->premios()->where('entregue', true)->count(),
        ]);
    }

    public function participantes(): JsonResponse
    {
        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
        }

        $usuarios = Usuario::with(['participacoes' => function ($q) use ($campanha) {
            $q->where('campanha_id', $campanha->id)->with('premio');
        }])->get();

        $tentativas = ParticipanteCampanha::where('campanha_id', $campanha->id)
            ->get()
            ->keyBy('usuario_id');

        return response()->json($usuarios->map(function (Usuario $usuario) use ($tentativas) {
            $participacao = $usuario->participacoes->last();
            $pc = $tentativas->get($usuario->id);

            return [
                'id' => $usuario->id,
                'nome' => $usuario->nome,
                'telefone' => $usuario->telefone,
                'estado' => $usuario->telefone_verificado ? 'validado' : 'pendente',
                'numero' => $participacao->numero ?? null,
                'resultado' => $participacao->resultado ?? null,
                'premio' => $participacao?->premio?->descricao,
                'participou_em' => $participacao->created_at ?? null,
                'tentativas_usadas' => $pc->tentativas_usadas ?? 0,
                'tentativas_disponiveis' => $pc->tentativas_disponiveis ?? 1,
            ];
        }));
    }

    public function concederTentativa(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
        ]);

        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
        }

        $usuario = Usuario::findOrFail($dados['usuario_id']);
        $participanteCampanha = $this->campanhaService->concederTentativaExtra($campanha, $usuario);

        return response()->json($participanteCampanha);
    }

    public function atividadeRecente(): JsonResponse
    {
        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
        }

        $atividades = Atividade::where('campanha_id', $campanha->id)
            ->with(['usuario', 'premio'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json($atividades->map(fn (Atividade $a) => [
            'tipo' => $a->tipo,
            'usuario_id' => $a->usuario_id,
            'nome' => $a->usuario?->nome,
            'numero' => $a->numero,
            'premio' => $a->premio?->descricao,
            'data_hora' => $a->created_at,
        ]));
    }

    public function vencedores(): JsonResponse
    {
        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
        }

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
            'premio' => $p->premio?->descricao,
            'entrega_estado' => $p->premio?->entregue ? 'entregue' : 'pendente',
            'data_hora' => $p->created_at,
        ]));
    }
}
