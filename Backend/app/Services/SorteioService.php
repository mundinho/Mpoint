<?php

namespace App\Services;

use App\Models\Campanha;
use App\Models\Participacao;
use App\Models\Quadrado;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class SorteioService
{
    public function __construct(
        private MozSmsService $smsService,
        private AuditoriaService $auditoria
    ) {
    }

    public function abrirQuadrado(Campanha $campanha, Usuario $usuario, int $numero): Participacao
    {
        $participacao = DB::transaction(function () use ($campanha, $usuario, $numero) {
            $usuarioBloqueado = Usuario::where('id', $usuario->id)->lockForUpdate()->first();

            $tentativasUsadas = Participacao::where('campanha_id', $campanha->id)->where('usuario_id', $usuario->id)->count();
            $tentativasPermitidas = 1 + $usuarioBloqueado->tentativas_extra;

            if ($tentativasUsadas >= $tentativasPermitidas) {
                throw new \RuntimeException('Este participante já esgotou as suas tentativas neste ciclo.');
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

            $categoriaTipo = $quadrado->premio?->categoria?->tipo;

            $resultado = match (true) {
                $quadrado->premio_id === null => 'nao_vencedor',
                $categoriaTipo === 'tentar_novamente' => 'tentar_novamente',
                default => 'vencedor',
            };

            if ($resultado === 'tentar_novamente') {
                $usuarioBloqueado->increment('tentativas_extra');
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

        try {
            if ($participacao->resultado === 'vencedor') {
                $premio = $participacao->premio;
                $this->smsService->enviar($usuario, 'vencedor', "Parabéns! Você ganhou: {$premio->descricao}. Contacte-nos para levantar o seu prémio.");
            } elseif ($participacao->resultado === 'tentar_novamente') {
                $this->smsService->enviar($usuario, 'tentar_novamente', 'Parabéns! Você ganhou uma nova tentativa. Jogue novamente!');
            } else {
                $this->smsService->enviar($usuario, 'nao_vencedor', 'Obrigado por participar! Desta vez não foi premiado, mas fique atento aos próximos ciclos.');
            }
        } catch (\RuntimeException $e) {
            report($e);
        }

        return $participacao;
    }
}
