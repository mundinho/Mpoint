<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Models\Otp;
use App\Models\Participacao;
use App\Models\Premio;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(private AuditoriaService $auditoria)
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
                'premio' => $participacao?->premio?->descricao,
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

    public function atividade(): JsonResponse
    {
        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json([]);
        }

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
                'premio' => $p->premio?->descricao,
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
                'premio' => $premio->descricao,
                'data_hora' => $premio->updated_at,
            ]);

        $atividade = $registos->concat($validacoes)->concat($participacoes)->concat($entregas)
            ->sortByDesc('data_hora')
            ->values()
            ->take(20);

        return response()->json($atividade);
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
