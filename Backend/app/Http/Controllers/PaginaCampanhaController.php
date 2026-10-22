<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Services\CampanhaImagemService;
use App\Support\CampanhaSlug;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Página e imagem de partilha de cada campanha ({link}.c.mpoints.site).
 *
 * Os robôs de pré-visualização (WhatsApp, Facebook…) não executam JavaScript, por isso
 * o HTML do jogo é servido aqui com o título, a descrição e a imagem da campanha.
 * O resto (JS, CSS, imagens) continua a ser servido directamente pelo Apache.
 */
class PaginaCampanhaController extends Controller
{
    public function __construct(private CampanhaImagemService $imagens)
    {
    }

    public function pagina(Request $request): Response
    {
        $indice = config('campanhas.spa_index');
        $html = is_file($indice) ? file_get_contents($indice) : null;

        if (!$html) {
            abort(503, 'Página indisponível.');
        }

        $campanha = $this->campanhaDoPedido($request);

        if ($campanha) {
            $html = $this->comEtiquetas($html, $campanha);
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-cache',
        ]);
    }

    public function imagem(Request $request)
    {
        $campanha = $this->campanhaDoPedido($request);
        $caminho = $campanha ? $this->imagens->gerar($campanha) : null;

        if (!$caminho) {
            abort(404);
        }

        return response()->file($caminho, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function campanhaDoPedido(Request $request): ?Campanha
    {
        $sufixo = config('campanhas.host_suffix');
        $host = strtolower($request->getHost());

        if (!$sufixo || !str_ends_with($host, '.' . $sufixo)) {
            return null;
        }

        $slug = substr($host, 0, -strlen('.' . $sufixo));

        return CampanhaSlug::valido($slug) ? Campanha::where('slug', $slug)->first() : null;
    }

    private function comEtiquetas(string $html, Campanha $campanha): string
    {
        $nome = trim(preg_replace('/\s+/', ' ', str_replace('_', ' ', (string) $campanha->nome))) ?: 'Campanha MPoints';
        $link = CampanhaSlug::link($campanha->slug) . '/';
        $imagem = CampanhaSlug::link($campanha->slug) . '/og-imagem.png?v=' . $this->imagens->versao($campanha);
        $descricao = "Escolha um número e descubra se ganhou! Participe na campanha {$nome}.";

        $e = fn ($valor) => htmlspecialchars($valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $etiquetas = implode("\n    ", [
            '<meta name="description" content="' . $e($descricao) . '">',
            '<meta property="og:type" content="website">',
            '<meta property="og:site_name" content="MPoints">',
            '<meta property="og:locale" content="pt_PT">',
            '<meta property="og:url" content="' . $e($link) . '">',
            '<meta property="og:title" content="' . $e($nome) . '">',
            '<meta property="og:description" content="' . $e($descricao) . '">',
            '<meta property="og:image" content="' . $e($imagem) . '">',
            '<meta property="og:image:secure_url" content="' . $e($imagem) . '">',
            '<meta property="og:image:type" content="image/png">',
            '<meta property="og:image:width" content="1200">',
            '<meta property="og:image:height" content="630">',
            '<meta property="og:image:alt" content="' . $e($nome) . '">',
            '<meta name="twitter:card" content="summary_large_image">',
            '<meta name="twitter:title" content="' . $e($nome) . '">',
            '<meta name="twitter:description" content="' . $e($descricao) . '">',
            '<meta name="twitter:image" content="' . $e($imagem) . '">',
        ]);

        $html = preg_replace('#<title>.*?</title>#s', '<title>' . $e($nome . ' · MPoints') . '</title>', $html, 1);

        return str_replace('</head>', "    {$etiquetas}\n  </head>", $html);
    }
}
