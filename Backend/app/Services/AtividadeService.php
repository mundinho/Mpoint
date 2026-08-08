<?php

namespace App\Services;

use App\Models\Atividade;

class AtividadeService
{
    public function registrar(
        ?int $campanhaId,
        string $tipo,
        ?int $usuarioId = null,
        ?int $numero = null,
        ?int $premioId = null,
        ?string $descricao = null
    ): Atividade {
        return Atividade::create([
            'campanha_id' => $campanhaId,
            'tipo' => $tipo,
            'usuario_id' => $usuarioId,
            'numero' => $numero,
            'premio_id' => $premioId,
            'descricao' => $descricao,
        ]);
    }
}
