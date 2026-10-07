<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

/**
 * Recria a base de dados da demonstração com dados de exemplo.
 * Só corre com DEMO_MODE=true e numa base de dados cujo nome termine em "_demo",
 * para nunca poder apagar a produção.
 */
class DemoReset extends Command
{
    protected $signature = 'demo:reset {--force : Não pedir confirmação}';

    protected $description = 'Recria a base de dados da demonstração com dados de exemplo';

    public function handle(): int
    {
        if (!self::ambienteDeDemo()) {
            $this->error('Recusado: DEMO_MODE desligado ou base de dados sem o sufixo "_demo".');
            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm('Apagar e recriar todos os dados da demonstração?')) {
            return self::SUCCESS;
        }

        $this->call('migrate:fresh', ['--force' => true]);
        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        $this->info('Demonstração reiniciada.');

        return self::SUCCESS;
    }

    public static function ambienteDeDemo(): bool
    {
        $ligacao = config('database.default');
        $base = (string) config("database.connections.{$ligacao}.database");

        return config('demo.enabled') === true && str_ends_with($base, '_demo');
    }
}
