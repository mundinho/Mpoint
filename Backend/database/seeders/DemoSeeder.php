<?php

namespace Database\Seeders;

use App\Console\Commands\DemoReset;
use App\Models\Administrador;
use App\Models\Campanha;
use App\Models\Otp;
use App\Models\Participacao;
use App\Models\Premio;
use App\Models\PremioBanco;
use App\Models\Quadrado;
use App\Models\Sms;
use App\Models\Usuario;
use App\Services\CampanhaService;
use App\Services\SorteioService;
use App\Support\DemoSms;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dados fictícios para demo.mpoints.emedx.co.mz.
 * Usa os serviços reais (distribuição de prémios e sorteio), por isso os
 * números, resultados, SMS e relatórios são coerentes entre si.
 */
class DemoSeeder extends Seeder
{
    private const NOMES = [
        'Ana', 'Carlos', 'Fátima', 'João', 'Luísa', 'Pedro', 'Rosa', 'Hélder', 'Inês', 'Nelson',
        'Sara', 'Tomás', 'Vânia', 'Edson', 'Célia', 'Rui', 'Marta', 'Abel', 'Joana', 'Dércio',
        'Amélia', 'Arsénio', 'Lurdes', 'Benedito', 'Graça', 'Zacarias', 'Felizarda', 'Gildo',
        'Hortência', 'Ilídio', 'Josina', 'Lázaro', 'Matilde', 'Orlando', 'Paulina', 'Samuel',
    ];

    private const APELIDOS = [
        'Machava', 'Nhantumbo', 'Cossa', 'Mondlane', 'Sitoe', 'Macuácua', 'Tembe', 'Chissano',
        'Muianga', 'Bila', 'Zandamela', 'Mabunda', 'Langa', 'Matsinhe', 'Massinga', 'Guambe',
        'Novela', 'Cumbe', 'Uamusse', 'Nhaca', 'Chivambo', 'Mucavele', 'Manhiça', 'Sibinde',
    ];

    private array $telefonesUsados = [];

    public function __construct(
        private CampanhaService $campanhas,
        private SorteioService $sorteio
    ) {
    }

    public function run(): void
    {
        if (!DemoReset::ambienteDeDemo()) {
            throw new \RuntimeException('DemoSeeder só pode correr no ambiente de demonstração.');
        }

        mt_srand();

        Administrador::create([
            'telefone' => config('demo.admin_telefone'),
            'nome' => config('demo.admin_nome'),
            'ativo' => true,
        ]);

        $banco = collect([
            ['Smartphone', 'Android, 128 GB', 3],
            ['Auriculares sem fios', 'Bluetooth, com estojo de carga', 6],
            ['Voucher de compras 1.000 MT', 'Válido nas lojas aderentes', 8],
            ['Cabaz alimentar', 'Produtos de primeira necessidade', 10],
            ['Recarga de saldo 500 MT', 'Crédito de telemóvel', 20],
            ['T-shirt da campanha', 'Edição limitada', 1],
        ])->mapWithKeys(fn ($p) => [$p[0] => PremioBanco::create([
            'nome' => $p[0],
            'descricao' => $p[1],
            'quantidade_padrao' => $p[2],
        ])]);

        // 1. Campanha anterior, já encerrada
        $inverno = $this->criarCampanha('Campanha Inverno 2026', 200, Carbon::create(2026, 6, 1), Carbon::create(2026, 7, 31));
        $this->adicionarPremios($inverno, $banco, [
            'Cabaz alimentar' => 6,
            'Recarga de saldo 500 MT' => 10,
            'Voucher de compras 1.000 MT' => 4,
        ]);
        $this->simularParticipantes($inverno, 90, Carbon::create(2026, 6, 2), Carbon::create(2026, 7, 30), entregues: 1.0);
        $inverno->update(['estado' => 'encerrada']);

        // 2. Campanha em curso — é a que o jogo mostra
        $inicio = now()->subDays(6)->startOfDay();
        $primavera = $this->criarCampanha('Campanha Primavera 2026', 500, $inicio, now()->addDays(24)->endOfDay());
        $this->adicionarPremios($primavera, $banco, [
            'Smartphone' => 3,
            'Auriculares sem fios' => 6,
            'Voucher de compras 1.000 MT' => 8,
            'Cabaz alimentar' => 10,
            'Recarga de saldo 500 MT' => 20,
        ]);
        $this->campanhas->adicionarPremioCampanha($primavera, $banco['T-shirt da campanha'], 'manual', [
            'numero' => $this->numeroLivre($primavera),
            'data_programada' => now()->toDateString(),
        ]);
        $primavera->update(['total_premios' => Premio::where('campanha_id', $primavera->id)->count()]);
        $this->simularParticipantes($primavera, 140, $inicio, now()->subMinutes(20), entregues: 0.6);

        // 3. Próxima campanha, preparada e em pausa
        $festas = $this->criarCampanha('Campanha Festas 2026', 300, Carbon::create(2026, 12, 1), Carbon::create(2026, 12, 31));
        $this->adicionarPremios($festas, $banco, [
            'Smartphone' => 2,
            'Cabaz alimentar' => 12,
            'Recarga de saldo 500 MT' => 15,
        ]);

        $primavera->update(['estado' => 'ativa']);

        DemoSms::retirar();
    }

    private function criarCampanha(string $nome, int $total, Carbon $inicio, Carbon $fim): Campanha
    {
        $campanha = Campanha::create([
            'nome' => $nome,
            'total_quadrados' => $total,
            'total_premios' => 0,
            'estado' => 'pausada',
            'data_inicio' => $inicio,
            'data_fim' => $fim,
            'otp_validade_minutos' => 5,
            'sms_resultado_ativo' => true,
        ]);

        $agora = now();
        collect(range(1, $total))
            ->map(fn (int $numero) => [
                'campanha_id' => $campanha->id,
                'numero' => $numero,
                'premio_id' => null,
                'estado' => 'disponivel',
                'aberto_por' => null,
                'aberto_em' => null,
                'created_at' => $agora,
                'updated_at' => $agora,
            ])
            ->chunk(200)
            ->each(fn ($linhas) => Quadrado::insert($linhas->toArray()));

        return $campanha;
    }

    private function adicionarPremios(Campanha $campanha, $banco, array $quantidades): void
    {
        foreach ($quantidades as $nome => $quantidade) {
            $this->campanhas->adicionarPremioCampanha($campanha, $banco[$nome], 'aleatorio', [
                'quantidade' => $quantidade,
                'logica_aleatoriedade' => 'uniforme',
            ]);
        }

        $campanha->update(['total_premios' => Premio::where('campanha_id', $campanha->id)->count()]);
    }

    private function numeroLivre(Campanha $campanha): int
    {
        return Quadrado::where('campanha_id', $campanha->id)
            ->whereNull('premio_id')
            ->where('estado', 'disponivel')
            ->inRandomOrder()
            ->value('numero');
    }

    private function simularParticipantes(Campanha $campanha, int $quantidade, Carbon $de, Carbon $ate, float $entregues): void
    {
        for ($i = 0; $i < $quantidade; $i++) {
            $momento = $this->momentoNoHorario($de, $ate);

            $usuario = Usuario::create([
                'nome' => self::NOMES[array_rand(self::NOMES)] . ' ' . self::APELIDOS[array_rand(self::APELIDOS)],
                'telefone' => $this->telefone(),
                'telefone_verificado' => true,
                'tentativas_extra' => mt_rand(1, 20) === 1 ? 1 : 0,
            ]);

            $otp = $usuario->otps()->create([
                'codigo_hash' => Hash::make((string) mt_rand(100000, 999999)),
                'expira_em' => $momento->copy()->addMinutes(5),
                'tentativas' => 0,
                'validado_em' => $momento->copy()->subMinute(),
            ]);

            Sms::create([
                'usuario_id' => $usuario->id,
                'tipo' => 'otp',
                'mesnagem' => 'O seu código MPoint é ******. Válido por 5 minutos.',
                'estado' => mt_rand(1, 40) === 1 ? 'falhado' : 'enviado',
                'enviado_em' => $momento->copy()->subMinutes(2),
            ]);

            $participacao = $this->sorteio->abrirQuadrado($campanha, $usuario, $this->numeroDisponivel($campanha));

            // Espalha os registos no tempo para os gráficos por hora fazerem sentido
            Usuario::where('id', $usuario->id)->update(['created_at' => $momento->copy()->subMinutes(3), 'updated_at' => $momento]);
            Otp::where('id', $otp->id)->update(['created_at' => $momento->copy()->subMinutes(2), 'updated_at' => $momento]);
            Participacao::where('id', $participacao->id)->update(['created_at' => $momento, 'updated_at' => $momento]);
            Quadrado::where('id', $participacao->quadrado_id)->update(['aberto_em' => $momento, 'updated_at' => $momento]);
            Sms::where('usuario_id', $usuario->id)->update(['created_at' => $momento, 'updated_at' => $momento]);

            if ($participacao->premio_id && mt_rand(1, 100) <= $entregues * 100) {
                Premio::where('id', $participacao->premio_id)->update([
                    'entregue' => true,
                    'updated_at' => $momento->copy()->addHours(mt_rand(1, 30))->min(now()),
                ]);
            }
        }

        DemoSms::retirar();
    }

    private function numeroDisponivel(Campanha $campanha): int
    {
        return Quadrado::where('campanha_id', $campanha->id)
            ->where('estado', 'disponivel')
            ->inRandomOrder()
            ->value('numero');
    }

    // Momento aleatório entre $de e $ate, entre as 08:00 e as 20:59, com mais movimento à tarde
    private function momentoNoHorario(Carbon $de, Carbon $ate): Carbon
    {
        do {
            $momento = Carbon::createFromTimestamp(mt_rand($de->timestamp, $ate->timestamp));
            $hora = [8, 9, 10, 10, 11, 11, 12, 13, 14, 15, 15, 16, 16, 17, 17, 17, 18, 18, 19, 20][mt_rand(0, 19)];
            $momento->setTime($hora, mt_rand(0, 59), mt_rand(0, 59));
        } while ($momento->greaterThan($ate) || $momento->lessThan($de));

        return $momento;
    }

    private function telefone(): string
    {
        do {
            $telefone = '2588' . [2, 4, 5, 6, 7][mt_rand(0, 4)] . mt_rand(1000000, 9999999);
        } while (isset($this->telefonesUsados[$telefone]));

        $this->telefonesUsados[$telefone] = true;

        return $telefone;
    }
}
