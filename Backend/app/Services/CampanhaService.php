<?php

namespace App\Services;

use App\Models\Campanha;
use App\Models\CategoriaPremio;
use App\Models\DistribuicaoAleatoria;
use App\Models\Premio;
use App\Models\Quadrado;
use Illuminate\Support\Facades\DB;

class CampanhaService
{
    public function __construct(private AuditoriaService $auditoria)
    {
    }

    public function resetOperacional(): Campanha
    {
        $nova = DB::transaction(function () {
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

        $this->auditoria->registrar('Campanha', 'reset_operacional', true, "Novo ciclo criado (campanha {$nova->id}), ciclo anterior encerrado.");

        return $nova;
    }

    /**
     * @param array<int, array{numero:int, descricao:string, valor_estimado:?float}> $premios
     */
    public function definirNumerosPremiados(Campanha $campanha, array $premios): Campanha
    {
        return DB::transaction(function () use ($campanha, $premios) {
            if ($campanha->participacoes()->exists()) {
                throw new \RuntimeException('Não é possível redefinir os prémios: já existem participações neste ciclo.');
            }

            if (count($premios) !== $campanha->total_premios) {
                throw new \RuntimeException("É necessário indicar exactamente {$campanha->total_premios} números premiados.");
            }

            $numeros = collect($premios)->pluck('numero');

            if ($numeros->unique()->count() !== $numeros->count()) {
                throw new \RuntimeException('Números repetidos na lista de prémios.');
            }

            if ($numeros->contains(fn ($n) => $n < 1 || $n > $campanha->total_quadrados)) {
                throw new \RuntimeException("Números devem estar entre 1 e {$campanha->total_quadrados}.");
            }

            // Limpa a atribuição actual antes de reatribuir.
            Quadrado::where('campanha_id', $campanha->id)->update(['premio_id' => null]);
            Premio::where('campanha_id', $campanha->id)->delete();

            foreach ($premios as $item) {
                $premio = Premio::create([
                    'campanha_id' => $campanha->id,
                    'descricao' => $item['descricao'],
                    'valor_estimado' => $item['valor_estimado'] ?? null,
                ]);

                Quadrado::where('campanha_id', $campanha->id)
                    ->where('numero', $item['numero'])
                    ->update(['premio_id' => $premio->id]);
            }

            return $campanha->fresh();
        });
    }

    /**
     * @param array<int, array{categoria_id:int, quantidade:int, data_programada:?string}> $linhas
     */
    public function configurarDistribuicaoAleatoria(Campanha $campanha, array $linhas): Campanha
    {
        return DB::transaction(function () use ($campanha, $linhas) {
            if ($campanha->participacoes()->exists()) {
                throw new \RuntimeException('Não é possível reconfigurar a distribuição: já existem participações neste ciclo.');
            }

            $categoriaIds = collect($linhas)->pluck('categoria_id')->unique();
            $categoriasExistentes = CategoriaPremio::whereIn('id', $categoriaIds)->pluck('nome', 'id');

            if ($categoriasExistentes->count() !== $categoriaIds->count()) {
                throw new \RuntimeException('Uma ou mais categorias indicadas não existem.');
            }

            $totalQuantidade = collect($linhas)->sum('quantidade');

            if ($totalQuantidade < 1 || $totalQuantidade > $campanha->total_quadrados) {
                throw new \RuntimeException("A quantidade total de prémios deve estar entre 1 e {$campanha->total_quadrados}.");
            }

            $this->limparPremiosAtuais($campanha);

            $numerosDisponiveis = collect(range(1, $campanha->total_quadrados))->shuffle();
            $cursor = 0;

            foreach ($linhas as $linha) {
                $categoriaNome = $categoriasExistentes[$linha['categoria_id']];

                for ($i = 0; $i < $linha['quantidade']; $i++) {
                    $numero = $numerosDisponiveis[$cursor++];

                    $premio = Premio::create([
                        'campanha_id' => $campanha->id,
                        'categoria_id' => $linha['categoria_id'],
                        'descricao' => $categoriaNome,
                        'valor_estimado' => null,
                        'data_programada' => $linha['data_programada'] ?? null,
                    ]);

                    Quadrado::where('campanha_id', $campanha->id)
                        ->where('numero', $numero)
                        ->update(['premio_id' => $premio->id]);
                }

                DistribuicaoAleatoria::create([
                    'campanha_id' => $campanha->id,
                    'categoria_id' => $linha['categoria_id'],
                    'quantidade' => $linha['quantidade'],
                    'data_programada' => $linha['data_programada'] ?? null,
                ]);
            }

            $campanha->update(['modo_distribuicao' => 'aleatorio']);

            $this->auditoria->registrar('Campanha', 'distribuicao_aleatoria', true, "Distribuição aleatória configurada para a campanha {$campanha->id} ({$totalQuantidade} números premiados).");

            return $campanha->fresh();
        });
    }

    /**
     * @param array<int, array{numero:int, categoria_id:int, descricao:string, data_programada:?string}> $premios
     */
    public function configurarDistribuicaoManual(Campanha $campanha, array $premios): Campanha
    {
        return DB::transaction(function () use ($campanha, $premios) {
            if ($campanha->participacoes()->exists()) {
                throw new \RuntimeException('Não é possível reconfigurar a distribuição: já existem participações neste ciclo.');
            }

            $numeros = collect($premios)->pluck('numero');

            if ($numeros->unique()->count() !== $numeros->count()) {
                throw new \RuntimeException('Números repetidos na lista de prémios.');
            }

            if ($numeros->contains(fn ($n) => $n < 1 || $n > $campanha->total_quadrados)) {
                throw new \RuntimeException("Números devem estar entre 1 e {$campanha->total_quadrados}.");
            }

            $categoriaIds = collect($premios)->pluck('categoria_id')->unique();

            if (CategoriaPremio::whereIn('id', $categoriaIds)->count() !== $categoriaIds->count()) {
                throw new \RuntimeException('Uma ou mais categorias indicadas não existem.');
            }

            $this->limparPremiosAtuais($campanha);

            foreach ($premios as $item) {
                $premio = Premio::create([
                    'campanha_id' => $campanha->id,
                    'categoria_id' => $item['categoria_id'],
                    'descricao' => $item['descricao'],
                    'valor_estimado' => null,
                    'data_programada' => $item['data_programada'] ?? null,
                ]);

                Quadrado::where('campanha_id', $campanha->id)
                    ->where('numero', $item['numero'])
                    ->update(['premio_id' => $premio->id]);
            }

            $campanha->update(['modo_distribuicao' => 'manual']);

            $this->auditoria->registrar('Campanha', 'distribuicao_manual', true, "Distribuição manual configurada para a campanha {$campanha->id} (" . count($premios) . ' números premiados).');

            return $campanha->fresh();
        });
    }

    private function limparPremiosAtuais(Campanha $campanha): void
    {
        Quadrado::where('campanha_id', $campanha->id)->update(['premio_id' => null]);
        Premio::where('campanha_id', $campanha->id)->delete();
        DistribuicaoAleatoria::where('campanha_id', $campanha->id)->delete();
    }

    public function associarPremio(Campanha $campanha, int $numero, string $descricao, ?float $valorEstimado, ?string $dataProgramada): Premio
    {
        return DB::transaction(function () use ($campanha, $numero, $descricao, $valorEstimado, $dataProgramada) {
            $quadrado = Quadrado::where('campanha_id', $campanha->id)->where('numero', $numero)->lockForUpdate()->first();

            if (!$quadrado) {
                throw new \RuntimeException('Número inválido.');
            }

            if ($quadrado->premio_id !== null) {
                throw new \RuntimeException('Este número já tem um prémio associado. Use a edição para alterar.');
            }

            if ($quadrado->estado !== 'disponivel') {
                throw new \RuntimeException('Não é possível associar prémio a um número já aberto.');
            }

            $premio = Premio::create([
                'campanha_id' => $campanha->id,
                'descricao' => $descricao,
                'valor_estimado' => $valorEstimado,
                'data_programada' => $dataProgramada,
            ]);

            $quadrado->update(['premio_id' => $premio->id]);

            $this->auditoria->registrar('Premio', 'associar', true, "Prémio '{$descricao}' associado ao número {$numero} (campanha {$campanha->id}).");

            return $premio;
        });
    }

    public function editarPremio(Campanha $campanha, int $numero, array $dados): Premio
    {
        $quadrado = Quadrado::where('campanha_id', $campanha->id)->where('numero', $numero)->first();

        if (!$quadrado || !$quadrado->premio_id) {
            throw new \RuntimeException('Este número não tem prémio associado.');
        }

        $premio = Premio::findOrFail($quadrado->premio_id);
        $premio->update(array_filter($dados, fn ($v) => $v !== null));

        $this->auditoria->registrar('Premio', 'editar', true, "Prémio do número {$numero} (campanha {$campanha->id}) editado.");

        return $premio->fresh();
    }

    public function removerPremio(Campanha $campanha, int $numero): void
    {
        DB::transaction(function () use ($campanha, $numero) {
            $quadrado = Quadrado::where('campanha_id', $campanha->id)->where('numero', $numero)->lockForUpdate()->first();

            if (!$quadrado || !$quadrado->premio_id) {
                throw new \RuntimeException('Este número não tem prémio associado.');
            }

            $premioId = $quadrado->premio_id;
            $quadrado->update(['premio_id' => null]);
            Premio::where('id', $premioId)->delete();

            $this->auditoria->registrar('Premio', 'remover', true, "Prémio removido do número {$numero} (campanha {$campanha->id}).");
        });
    }

    public function atualizarConfiguracoes(Campanha $campanha, array $dados): Campanha
    {
        return DB::transaction(function () use ($campanha, $dados) {
            if (isset($dados['total_quadrados']) && $dados['total_quadrados'] !== $campanha->total_quadrados) {
                if ($campanha->participacoes()->exists()) {
                    throw new \RuntimeException('Não é possível alterar o total de números: já existem participações neste ciclo.');
                }

                $novoTotal = $dados['total_quadrados'];

                Quadrado::where('campanha_id', $campanha->id)->delete();
                Premio::where('campanha_id', $campanha->id)->delete();

                $agora = now();
                $linhas = collect(range(1, $novoTotal))->map(fn (int $numero) => [
                    'campanha_id' => $campanha->id,
                    'numero' => $numero,
                    'premio_id' => null,
                    'estado' => 'disponivel',
                    'aberto_por' => null,
                    'aberto_em' => null,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ]);
                $linhas->chunk(200)->each(fn ($chunk) => Quadrado::insert($chunk->toArray()));
            }

            $campanha->update(array_intersect_key($dados, array_flip([
                'nome', 'data_inicio', 'data_fim', 'total_quadrados', 'total_premios', 'otp_validade_minutos',
            ])));

            return $campanha->fresh();
        });
    }

    public function activar(Campanha $campanha): Campanha
    {
        $campanha->update(['estado' => 'ativa']);
        $this->auditoria->registrar('Campanha', 'activar', true, "Campanha {$campanha->id} activada.");
        return $campanha->fresh();
    }

    public function pausar(Campanha $campanha): Campanha
    {
        $campanha->update(['estado' => 'pausada']);
        $this->auditoria->registrar('Campanha', 'pausar', true, "Campanha {$campanha->id} pausada.");
        return $campanha->fresh();
    }

    public function encerrar(Campanha $campanha): Campanha
    {
        $campanha->update(['estado' => 'encerrada']);
        $this->auditoria->registrar('Campanha', 'encerrar', true, "Campanha {$campanha->id} encerrada.");
        return $campanha->fresh();
    }
}
