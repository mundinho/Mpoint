<?php

namespace Database\Seeders;

use App\Models\Campanha;
use App\Models\Premio;
use App\Models\Quadrado;
use Illuminate\Database\Seeder;

class CampanhaSeeder extends Seeder
{
    public function run(): void
    {
        $campanha = Campanha::create([
            'total_quadrados' => 1000,
            'total_premios' => 10,
            'estado' => 'ativa',
            'data_inicio' => now(),
        ]);

        $premios = collect(range(1, 10))->map(function (int $i) use ($campanha) {
            return Premio::create([
                'campanha_id' => $campanha->id,
                'nome' => "Prémio {$i}",
            ]);
        });

        $numerosPremiados = collect(range(1, 1000))->random(10)->values();

        $premioPorNumero = $numerosPremiados->mapWithKeys(function ($numero, $index) use ($premios) {
            return [$numero => $premios[$index]->id];
        });

        $agora = now();
        $linhas = collect(range(1, 1000))->map(function (int $numero) use ($campanha, $premioPorNumero, $agora) {
            return [
                'campanha_id' => $campanha->id,
                'numero' => $numero,
                'premio_id' => $premioPorNumero->get($numero),
                'estado' => 'disponivel',
                'aberto_por' => null,
                'aberto_em' => null,
                'created_at' => $agora,
                'updated_at' => $agora,
            ];
        });

        $linhas->chunk(200)->each(function ($chunk) {
            Quadrado::insert($chunk->toArray());
        });
    }
}
