<?php

namespace App\Services;

use App\Models\Campanha;
use App\Models\Participacao;
use App\Models\ParticipanteCampanha;
use App\Models\Quadrado;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class SorteioService
{
    public function __construct(
        private MozSmsService $smsService,
        private AuditoriaService $auditoria,
        private AtividadeService $atividade
    ) {
    }

    public function abrirQuadrado(Campanha $campanha, Usuario $usuario, int $numero): Participacao
    {
        $participacao = DB::transaction(function () use ($campanha, $usuario, $numero) {
            $participanteCampanha = ParticipanteCampanha::where('usuario_id', $usuario->id)
                ->where('campanha_id', $campanha->id)
                ->lockForUpdate()
                ->first();

            if (!$participanteCampanha) {
                $participanteCampanha = ParticipanteCampanha::create([
                    'usuario_id' => $usuario->id,
                    'campanha_id' => $campanha->id,
                    'tentativas_disponiveis' => 1,
                    'tentativas_usadas' => 0,
                ]);
            }

            if ($participanteCampanha->tentativas_usadas >= $participanteCampanha->tentativas_disponiveis) {
                throw new \RuntimeException('Sem tentativas disponíveis neste ciclo.');
            }

            $quadrado = Quadrado::where('campanha_id', $campanha->id)
                ->where('numero', $numero)
                ->lockForUpdate()
                ->first();

            if (!$quadrado) {
                throw new \RuntimeException('Número inválido.');
            }

            if ($quadrado->estado !== 'disponivel') {
                throw new \RuntimeException('Este número já foi aberto.');
            }

            $quadrado->update([
                'estado' => 'aberto',
                'aberto_por' => $usuario->id,
                'aberto_em' => now(),
            ]);

            $premio = $quadrado->premio;
            $ehTentarNovamente = $premio?->categoria?->tipo === 'tentar_novamente';

            if ($ehTentarNovamente) {
                $resultado = 'tentar_novamente';
            } else {
                $resultado = $premio ? 'vencedor' : 'nao_vencedor';
            }

            $participanteCampanha->increment('tentativas_usadas');

            if ($ehTentarNovamente) {
                $participanteCampanha->increment('tentativas_disponiveis');
            }

            return Participacao::create([
                'campanha_id' => $campanha->id,
                'usuario_id' => $usuario->id,
                'quadrado_id' => $quadrado->id,
                'numero' => $numero,
                'resultado' => $resultado,
                'premio_id' => $quadrado->premio_id,
            ]);
        });

        $this->auditoria->registrar(
            'Participacao',
            'sorteio_abrir',
            true,
            "Usuario {$usuario->id} abriu o número {$numero} na campanha {$campanha->id}: {$participacao->resultado}"
        );

        $this->atividade->registrar($campanha->id, 'participacao', $usuario->id, $numero, $participacao->premio_id, "{$usuario->nome} abriu o número {$numero}.");

        if ($participacao->resultado === 'tentar_novamente') {
            $this->atividade->registrar($campanha->id, 'tentar_novamente', $usuario->id, $numero, null, "{$usuario->nome} saiu 'Tentar Novamente' e ganhou mais uma tentativa.");
        } elseif ($participacao->resultado === 'vencedor') {
            $this->atividade->registrar($campanha->id, 'vencedor', $usuario->id, $numero, $participacao->premio_id, "{$usuario->nome} venceu no número {$numero}.");
        }

        try {
            if ($participacao->resultado === 'vencedor') {
                $premio = $participacao->premio;
                $this->smsService->enviar($usuario, 'vencedor', "Parabéns! Você ganhou: {$premio->descricao}. Contacte-nos para levantar o seu prémio.");
            } elseif ($participacao->resultado === 'tentar_novamente') {
                $this->smsService->enviar($usuario, 'tentar_novamente', 'Quase lá! Você ganhou mais uma tentativa. Tente novamente!');
            } else {
                $this->smsService->enviar($usuario, 'nao_vencedor', 'Obrigado por participar! Desta vez não foi premiado, mas fique atento aos próximos ciclos.');
            }
        } catch (\RuntimeException $e) {
            report($e);
        }

        return $participacao;
    }
}
