<?php

namespace App\Services;

use App\Models\Campanha;
use App\Models\CampanhaPremio;
use App\Models\DistribuicaoAleatoria;
use App\Models\Participacao;
use App\Models\Premio;
use App\Models\PremioBanco;
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
                    'nome' => "Prémio {$i}",
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
     * @param array<int, array{nome:string, quantidade:int, logica_aleatoriedade:?string, data_programada:?string}> $linhas
     */
    public function configurarDistribuicaoAleatoria(Campanha $campanha, array $linhas): Campanha
    {
        return DB::transaction(function () use ($campanha, $linhas) {
            if ($campanha->participacoes()->exists()) {
                throw new \RuntimeException('Não é possível reconfigurar a distribuição: já existem participações neste ciclo.');
            }

            $totalQuantidade = collect($linhas)->sum('quantidade');

            if ($totalQuantidade < 1 || $totalQuantidade > $campanha->total_quadrados) {
                throw new \RuntimeException("A quantidade total de prémios deve estar entre 1 e {$campanha->total_quadrados}.");
            }

            $this->limparPremiosAtuais($campanha);

            $disponiveis = collect(range(1, $campanha->total_quadrados))->shuffle()->values();

            foreach ($linhas as $linha) {
                $logica = $linha['logica_aleatoriedade'] ?? 'uniforme';
                $numeros = $this->selecionarNumeros($logica, $linha['quantidade'], $campanha->total_quadrados, $disponiveis);

                $premioBanco = PremioBanco::firstOrCreate(
                    ['nome' => $linha['nome']],
                    ['quantidade_padrao' => $linha['quantidade']]
                );

                $campanhaPremio = CampanhaPremio::create([
                    'campanha_id' => $campanha->id,
                    'premio_banco_id' => $premioBanco->id,
                    'modo_distribuicao' => 'aleatorio',
                    'quantidade' => $linha['quantidade'],
                    'logica_aleatoriedade' => $linha['logica_aleatoriedade'] ?? null,
                    'data_programada' => $linha['data_programada'] ?? null,
                ]);

                foreach ($numeros as $numero) {
                    $premio = Premio::create([
                        'campanha_id' => $campanha->id,
                        'campanha_premio_id' => $campanhaPremio->id,
                        'nome' => $linha['nome'],
                        'data_programada' => $linha['data_programada'] ?? null,
                    ]);

                    Quadrado::where('campanha_id', $campanha->id)
                        ->where('numero', $numero)
                        ->update(['premio_id' => $premio->id]);

                    $disponiveis = $disponiveis->reject(fn ($n) => $n === $numero)->values();
                }

                DistribuicaoAleatoria::create([
                    'campanha_id' => $campanha->id,
                    'nome' => $linha['nome'],
                    'quantidade' => $linha['quantidade'],
                    'logica_aleatoriedade' => $linha['logica_aleatoriedade'] ?? null,
                    'data_programada' => $linha['data_programada'] ?? null,
                ]);
            }

            $campanha->update(['modo_distribuicao' => 'aleatorio']);

            $this->auditoria->registrar('Campanha', 'distribuicao_aleatoria', true, "Distribuição aleatória configurada para a campanha {$campanha->id} ({$totalQuantidade} números premiados).");

            return $campanha->fresh();
        });
    }

    /**
     * @param array<int, array{numero:int, nome:string, data_programada:?string}> $premios
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

            $this->limparPremiosAtuais($campanha);

            foreach ($premios as $item) {
                $premioBanco = PremioBanco::firstOrCreate(
                    ['nome' => $item['nome']],
                    ['quantidade_padrao' => 1]
                );

                $campanhaPremio = CampanhaPremio::create([
                    'campanha_id' => $campanha->id,
                    'premio_banco_id' => $premioBanco->id,
                    'modo_distribuicao' => 'manual',
                    'quantidade' => 1,
                    'data_programada' => $item['data_programada'] ?? null,
                ]);

                $premio = Premio::create([
                    'campanha_id' => $campanha->id,
                    'campanha_premio_id' => $campanhaPremio->id,
                    'nome' => $item['nome'],
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

    /**
     * Escolhe $quantidade números dentro de $disponiveis, seguindo a lógica pedida:
     * - uniforme: amostra aleatória simples (o pool já vem baralhado).
     * - aritmetica: alvos igualmente espaçados ao longo de toda a gama de números,
     *   com um pequeno desvio aleatório por alvo — evita prémios agrupados numa zona.
     * - geometrica: alvos com espaçamento crescente (progressão geométrica), começando
     *   denso numa ponta da gama e ficando mais espaçado até à outra ponta.
     * Em ambos os casos não-uniformes, cada alvo é resolvido para o número disponível
     * mais próximo ainda por usar.
     *
     * @param \Illuminate\Support\Collection<int, int> $disponiveis
     * @return array<int, int>
     */
    private function selecionarNumeros(string $logica, int $quantidade, int $totalQuadrados, \Illuminate\Support\Collection $disponiveis): array
    {
        if ($quantidade < 1 || $disponiveis->isEmpty()) {
            return [];
        }

        if ($logica === 'uniforme') {
            return $disponiveis->take($quantidade)->values()->all();
        }

        $alvos = $logica === 'geometrica'
            ? $this->alvosGeometricos($quantidade, $totalQuadrados)
            : $this->alvosAritmeticos($quantidade, $totalQuadrados);

        $restantes = $disponiveis;
        $escolhidos = [];

        foreach ($alvos as $alvo) {
            if ($restantes->isEmpty()) {
                break;
            }

            $numero = $restantes->sortBy(fn ($n) => abs($n - $alvo))->first();
            $escolhidos[] = $numero;
            $restantes = $restantes->reject(fn ($n) => $n === $numero)->values();
        }

        return $escolhidos;
    }

    private function alvosAritmeticos(int $quantidade, int $totalQuadrados): array
    {
        $passo = $totalQuadrados / $quantidade;

        return collect(range(0, $quantidade - 1))
            ->map(fn ($i) => (int) round(($i + random_int(0, 60) / 100) * $passo) + 1)
            ->map(fn ($n) => max(1, min($totalQuadrados, $n)))
            ->all();
    }

    private function alvosGeometricos(int $quantidade, int $totalQuadrados): array
    {
        $razao = pow(max($totalQuadrados, 2), 1 / max($quantidade, 1));
        $inverter = (bool) random_int(0, 1);

        $alvos = collect(range(1, $quantidade))
            ->map(fn ($i) => (int) round(pow($razao, $i)))
            ->map(fn ($n) => max(1, min($totalQuadrados, $n)));

        return $inverter ? $alvos->map(fn ($n) => $totalQuadrados + 1 - $n)->all() : $alvos->all();
    }

    private function limparPremiosAtuais(Campanha $campanha): void
    {
        Quadrado::where('campanha_id', $campanha->id)->update(['premio_id' => null]);
        Premio::where('campanha_id', $campanha->id)->delete();
        DistribuicaoAleatoria::where('campanha_id', $campanha->id)->delete();
        CampanhaPremio::where('campanha_id', $campanha->id)->delete();
    }

    /**
     * Adiciona UM prémio do Banco à campanha, sem afectar os prémios já configurados
     * (ao contrário de configurarDistribuicaoManual/Aleatoria, que substituem tudo).
     * Cada prémio da campanha tem o seu próprio modo — a campanha pode ter prémios
     * manuais e aleatórios ao mesmo tempo.
     *
     * @param array{numero?:int, quantidade?:int, logica_aleatoriedade?:?string, data_programada?:?string} $dados
     */
    public function adicionarPremioCampanha(Campanha $campanha, PremioBanco $premioBanco, string $modoDistribuicao, array $dados): CampanhaPremio
    {
        return DB::transaction(function () use ($campanha, $premioBanco, $modoDistribuicao, $dados) {
            if ($modoDistribuicao === 'manual') {
                $numero = $dados['numero'];

                if ($numero < 1 || $numero > $campanha->total_quadrados) {
                    throw new \RuntimeException("Número deve estar entre 1 e {$campanha->total_quadrados}.");
                }

                $quadrado = Quadrado::where('campanha_id', $campanha->id)->where('numero', $numero)->lockForUpdate()->first();

                if (!$quadrado) {
                    throw new \RuntimeException('Número inválido.');
                }

                if ($quadrado->premio_id !== null) {
                    throw new \RuntimeException('Este número já tem um prémio associado.');
                }

                $campanhaPremio = CampanhaPremio::create([
                    'campanha_id' => $campanha->id,
                    'premio_banco_id' => $premioBanco->id,
                    'modo_distribuicao' => 'manual',
                    'quantidade' => 1,
                    'data_programada' => $dados['data_programada'] ?? null,
                ]);

                $premio = Premio::create([
                    'campanha_id' => $campanha->id,
                    'campanha_premio_id' => $campanhaPremio->id,
                    'nome' => $premioBanco->nome,
                    'data_programada' => $dados['data_programada'] ?? null,
                ]);

                $quadrado->update(['premio_id' => $premio->id]);
            } else {
                $quantidade = $dados['quantidade'];
                $logica = $dados['logica_aleatoriedade'] ?? 'uniforme';

                $disponiveis = Quadrado::where('campanha_id', $campanha->id)
                    ->where('estado', 'disponivel')
                    ->whereNull('premio_id')
                    ->pluck('numero')
                    ->shuffle()
                    ->values();

                if ($quantidade < 1 || $quantidade > $disponiveis->count()) {
                    throw new \RuntimeException("A quantidade deve estar entre 1 e {$disponiveis->count()} (números ainda livres nesta campanha).");
                }

                $campanhaPremio = CampanhaPremio::create([
                    'campanha_id' => $campanha->id,
                    'premio_banco_id' => $premioBanco->id,
                    'modo_distribuicao' => 'aleatorio',
                    'quantidade' => $quantidade,
                    'logica_aleatoriedade' => $logica,
                    'data_programada' => $dados['data_programada'] ?? null,
                ]);

                $numeros = $this->selecionarNumeros($logica, $quantidade, $campanha->total_quadrados, $disponiveis);

                foreach ($numeros as $numero) {
                    $premio = Premio::create([
                        'campanha_id' => $campanha->id,
                        'campanha_premio_id' => $campanhaPremio->id,
                        'nome' => $premioBanco->nome,
                        'data_programada' => $dados['data_programada'] ?? null,
                    ]);

                    Quadrado::where('campanha_id', $campanha->id)
                        ->where('numero', $numero)
                        ->update(['premio_id' => $premio->id]);
                }
            }

            $this->auditoria->registrar(
                'CampanhaPremio',
                'adicionar',
                true,
                "Prémio '{$premioBanco->nome}' ({$modoDistribuicao}) adicionado à campanha {$campanha->id}."
            );

            return $campanhaPremio->fresh();
        });
    }

    /**
     * Edita a configuração de um prémio já adicionado à campanha. Bloqueado se algum
     * dos seus números já tiver sido jogado. Para manual, permite mover para outro
     * número livre; para aleatório, permite aumentar/reduzir a quantidade (redesenha
     * só a diferença, não mexe nos números já sorteados).
     *
     * @param array{numero?:int, quantidade?:int, data_programada?:?string} $dados
     */
    public function editarPremioCampanha(CampanhaPremio $campanhaPremio, array $dados): CampanhaPremio
    {
        return DB::transaction(function () use ($campanhaPremio, $dados) {
            $campanha = $campanhaPremio->campanha;
            $premioIds = $campanhaPremio->premios()->pluck('id');

            if (Participacao::whereIn('premio_id', $premioIds)->exists()) {
                throw new \RuntimeException('Não é possível editar: já existe pelo menos uma participação num número deste prémio.');
            }

            if (array_key_exists('data_programada', $dados)) {
                $campanhaPremio->premios()->update(['data_programada' => $dados['data_programada']]);
            }

            if ($campanhaPremio->modo_distribuicao === 'manual' && isset($dados['numero'])) {
                $numero = $dados['numero'];

                if ($numero < 1 || $numero > $campanha->total_quadrados) {
                    throw new \RuntimeException("Número deve estar entre 1 e {$campanha->total_quadrados}.");
                }

                $novoQuadrado = Quadrado::where('campanha_id', $campanha->id)->where('numero', $numero)->lockForUpdate()->first();

                if (!$novoQuadrado) {
                    throw new \RuntimeException('Número inválido.');
                }

                if ($novoQuadrado->premio_id !== null && $novoQuadrado->premio_id !== $premioIds->first()) {
                    throw new \RuntimeException('Este número já tem um prémio associado.');
                }

                Quadrado::where('campanha_id', $campanha->id)->where('premio_id', $premioIds->first())->update(['premio_id' => null]);
                $novoQuadrado->update(['premio_id' => $premioIds->first()]);
            }

            if ($campanhaPremio->modo_distribuicao === 'aleatorio' && isset($dados['quantidade'])) {
                $quantidadeNova = $dados['quantidade'];
                $quantidadeAtual = $premioIds->count();

                if ($quantidadeNova > $quantidadeAtual) {
                    $aAdicionar = $quantidadeNova - $quantidadeAtual;

                    $disponiveis = Quadrado::where('campanha_id', $campanha->id)
                        ->where('estado', 'disponivel')
                        ->whereNull('premio_id')
                        ->pluck('numero')
                        ->shuffle()
                        ->values();

                    if ($aAdicionar > $disponiveis->count()) {
                        throw new \RuntimeException("Só há {$disponiveis->count()} números livres nesta campanha.");
                    }

                    $numeros = $this->selecionarNumeros(
                        $campanhaPremio->logica_aleatoriedade ?? 'uniforme',
                        $aAdicionar,
                        $campanha->total_quadrados,
                        $disponiveis
                    );

                    foreach ($numeros as $numero) {
                        $premio = Premio::create([
                            'campanha_id' => $campanha->id,
                            'campanha_premio_id' => $campanhaPremio->id,
                            'nome' => $campanhaPremio->premioBanco->nome,
                            'data_programada' => $dados['data_programada'] ?? $campanhaPremio->data_programada,
                        ]);

                        Quadrado::where('campanha_id', $campanha->id)
                            ->where('numero', $numero)
                            ->update(['premio_id' => $premio->id]);
                    }
                } elseif ($quantidadeNova < $quantidadeAtual) {
                    $premiosParaRemover = $campanhaPremio->premios()->latest('id')->take($quantidadeAtual - $quantidadeNova)->get();

                    foreach ($premiosParaRemover as $premio) {
                        Quadrado::where('premio_id', $premio->id)->update(['premio_id' => null]);
                        $premio->delete();
                    }
                }

                $campanhaPremio->update(['quantidade' => $quantidadeNova]);
            }

            $campanhaPremio->update(array_intersect_key($dados, array_flip(['data_programada'])));

            $this->auditoria->registrar('CampanhaPremio', 'editar', true, "Configuração do prémio da campanha (id {$campanhaPremio->id}) editada.");

            return $campanhaPremio->fresh();
        });
    }

    public function removerPremioCampanha(CampanhaPremio $campanhaPremio): void
    {
        DB::transaction(function () use ($campanhaPremio) {
            $premioIds = $campanhaPremio->premios()->pluck('id');

            if (Participacao::whereIn('premio_id', $premioIds)->exists()) {
                throw new \RuntimeException('Não é possível remover: já existe pelo menos uma participação num número deste prémio.');
            }

            Quadrado::whereIn('premio_id', $premioIds)->update(['premio_id' => null]);
            Premio::whereIn('id', $premioIds)->delete();

            $this->auditoria->registrar('CampanhaPremio', 'remover', true, "Prémio da campanha (id {$campanhaPremio->id}) removido.");

            $campanhaPremio->delete();
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
                'sms_resultado_ativo', 'texto_sms_resultado',
            ])));

            return $campanha->fresh();
        });
    }

    public function activar(Campanha $campanha): Campanha
    {
        return DB::transaction(function () use ($campanha) {
            $outrasAtivas = Campanha::where('estado', 'ativa')
                ->where('id', '!=', $campanha->id)
                ->lockForUpdate()
                ->get();

            foreach ($outrasAtivas as $outra) {
                $outra->update(['estado' => 'pausada']);
                $this->auditoria->registrar('Campanha', 'pausar_automatico', true, "Campanha {$outra->id} pausada automaticamente ao activar a campanha {$campanha->id}.");
            }

            $campanha->update(['estado' => 'ativa']);
            $this->auditoria->registrar('Campanha', 'activar', true, "Campanha {$campanha->id} activada.");

            return $campanha->fresh();
        });
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
