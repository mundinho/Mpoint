<?php

namespace App\Services;

use App\Models\Campanha;
use App\Models\Premio;
use App\Models\Quadrado;
use Illuminate\Support\Facades\DB;

class CampanhaService
{
    public function resetOperacional(): Campanha
    {
        return DB::transaction(function () {
            $atual = Campanha::where('estado', 'ativa')->lockForUpdate()->first();

            if ($atual) {
                $atual->update(['estado' => 'encerrada']);
            }

            $nova = Campanha::create([
                'total_quadrados' => 1000,
                'total_premios' => 10,
                'estado' => 'ativa',
                'data_inicio' => now(),
            ]);

            $premios = collect(range(1, 10))->map(function (int $i) use ($nova) {
                return Premio::create([
                    'campanha_id' => $nova->id,
                    'descricao' => "Prémio {$i}",
                    'valor_estimado' => null,
                ]);
            });

            $numerosPremiados = collect(range(1, 1000))->random(10)->values();
            $premioPorNumero = $numerosPremiados->mapWithKeys(fn ($numero, $index) => [$numero => $premios[$index]->id]);

            $agora = now();
            $linhas = collect(range(1, 1000))->map(fn (int $numero) => [
                'campanha_id' => $nova->id,
                'numero' => $numero,
                'premio_id' => $premioPorNumero->get($numero),
                'estado' => 'disponivel',
                'aberto_por' => null,
                'aberto_em' => null,
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);

            $linhas->chunk(200)->each(fn ($chunk) => Quadrado::insert($chunk->toArray()));

            return $nova;
        });
    }
}
