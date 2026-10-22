<?php

namespace App\Console\Commands;

use App\Models\Campanha;
use App\Support\CampanhaSlug;
use Illuminate\Console\Command;

/**
 * Lista os subdomínios das campanhas (um por linha), para o script do servidor
 * emitir os certificados HTTPS. Só devolve nomes válidos com o sufixo configurado.
 */
class CampanhasHosts extends Command
{
    protected $signature = 'campanhas:hosts';

    protected $description = 'Lista os subdomínios das campanhas (para certificados HTTPS)';

    public function handle(): int
    {
        if (!config('campanhas.host_suffix')) {
            $this->error('CAMPANHAS_HOST_SUFFIX não está definido.');
            return self::FAILURE;
        }

        Campanha::whereNotNull('slug')
            ->orderBy('id')
            ->get()
            ->map(fn (Campanha $campanha) => CampanhaSlug::host($campanha))
            ->filter()
            ->unique()
            ->each(fn (string $host) => $this->line($host));

        return self::SUCCESS;
    }
}
