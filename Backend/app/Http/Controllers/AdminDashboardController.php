<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Models\Participacao;
use App\Models\Premio;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
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

        return response()->json($usuarios->map(function (Usuario $usuario) {
            $participacao = $usuario->participacoes->first();

            return [
                'id' => $usuario->id,
                'nome' => $usuario->nome,
                'telefone' => $usuario->telefone,
                'estado' => $usuario->telefone_verificado ? 'validado' : 'pendente',
                'numero' => $participacao->numero ?? null,
                'resultado' => $participacao->resultado ?? null,
                'premio' => $participacao?->premio?->descricao,
                'participou_em' => $participacao->created_at ?? null,
            ];
        }));
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
