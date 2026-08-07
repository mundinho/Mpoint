<?php

namespace App\Services;

use App\Models\Campanha;
use App\Models\Participacao;
use App\Models\Quadrado;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class SorteioService
{
    public function __construct(private MozSmsService $smsService)
    {
    }

    public function abrirQuadrado(Campanha $campanha, Usuario $usuario, int $numero): Participacao
    {
        $participacao = DB::transaction(function () use ($campanha, $usuario, $numero) {
            if (Participacao::where('campanha_id', $campanha->id)->where('usuario_id', $usuario->id)->exists()) {
                throw new \RuntimeException('Este participante já participou neste ciclo.');
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

            $vencedor = $quadrado->premio_id !== null;

            return Participacao::create([
                'campanha_id' => $campanha->id,
                'usuario_id' => $usuario->id,
                'quadrado_id' => $quadrado->id,
                'numero' => $numero,
                'resultado' => $vencedor ? 'vencedor' : 'nao_vencedor',
                'premio_id' => $quadrado->premio_id,
            ]);
        });

        try {
            if ($participacao->resultado === 'vencedor') {
                $premio = $participacao->premio;
                $this->smsService->enviar($usuario, 'vencedor', "Parabéns! Você ganhou: {$premio->descricao}. Contacte-nos para levantar o seu prémio.");
            } else {
                $this->smsService->enviar($usuario, 'nao_vencedor', 'Obrigado por participar! Desta vez não foi premiado, mas fique atento aos próximos ciclos.');
            }
        } catch (\RuntimeException $e) {
            report($e);
        }

        return $participacao;
    }
}
