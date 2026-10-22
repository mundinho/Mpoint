<?php

namespace App\Services;

use App\Models\Campanha;
use App\Support\CampanhaSlug;
use Symfony\Component\Process\Process;

/**
 * Imagem de pré-visualização (Open Graph, 1200x630) de cada campanha — a que aparece
 * ao partilhar o link no WhatsApp, Facebook, LinkedIn, Telegram…
 *
 * Gerada pelo script resources/og/imagem_campanha.py (Pillow) ao criar a campanha e
 * sempre que o nome ou o link mudam; se faltar, é gerada no primeiro pedido.
 */
class CampanhaImagemService
{
    // Mudar quando o desenho da imagem mudar, para regenerar todas
    private const DESENHO = 'v1';

    public function caminho(Campanha $campanha): string
    {
        return storage_path('app/og/campanha-' . (int) $campanha->id . '-' . $this->versao($campanha) . '.png');
    }

    public function versao(Campanha $campanha): string
    {
        return substr(sha1(self::DESENHO . '|' . $campanha->nome . '|' . $campanha->slug), 0, 12);
    }

    /**
     * Devolve o caminho da imagem, gerando-a se ainda não existir. Null se não for possível.
     */
    public function gerar(Campanha $campanha): ?string
    {
        $caminho = $this->caminho($campanha);

        if (is_file($caminho)) {
            return $caminho;
        }

        $script = resource_path('og/imagem_campanha.py');
        $fonteBold = config('campanhas.og_fonte_bold');
        $fonte = config('campanhas.og_fonte');

        if (!is_file($script) || !is_file((string) $fonteBold) || !is_file((string) $fonte)) {
            report(new \RuntimeException('Imagem de campanha: script ou fontes em falta.'));
            return null;
        }

        $dados = json_encode([
            'nome' => (string) $campanha->nome,
            'host' => (string) (CampanhaSlug::host($campanha) ?? ''),
            'saida' => $caminho,
            'fonte_bold' => $fonteBold,
            'fonte' => $fonte,
        ], JSON_UNESCAPED_UNICODE);

        // Argumentos em lista (sem shell) e dados por stdin
        $processo = new Process([config('campanhas.og_python', 'python3'), $script]);
        $processo->setInput($dados);
        $processo->setTimeout(30);
        $processo->run();

        if (!$processo->isSuccessful() || !is_file($caminho)) {
            report(new \RuntimeException('Imagem de campanha falhou: ' . trim($processo->getErrorOutput())));
            return null;
        }

        $this->limparVersoesAntigas($campanha, $caminho);

        return $caminho;
    }

    private function limparVersoesAntigas(Campanha $campanha, string $atual): void
    {
        $pasta = storage_path('app/og');

        foreach (glob($pasta . '/campanha-' . (int) $campanha->id . '-*.png') ?: [] as $ficheiro) {
            if ($ficheiro !== $atual && str_starts_with(realpath($ficheiro) ?: '', realpath($pasta) . DIRECTORY_SEPARATOR)) {
                @unlink($ficheiro);
            }
        }
    }
}
