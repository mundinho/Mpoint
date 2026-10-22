<?php

namespace App\Support;

use App\Models\Campanha;
use Illuminate\Validation\Rule;

/**
 * Link (subdomínio) de uma campanha: {slug}.c.mpoints.site — ex.: "bci" ou "mbottlestore.rifa".
 * Até 3 níveis separados por pontos; cada nível só com letras minúsculas, números e
 * hífens, sem hífen nas pontas; 2 a 63 caracteres no total.
 */
class CampanhaSlug
{
    public const PADRAO = '/^(?=.{2,63}$)[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?){0,2}$/';

    public const MAX = 63;

    // Nomes que não podem ser usados por campanhas (infra-estrutura e confusão com o sistema)
    public const RESERVADOS = [
        'admin', 'api', 'www', 'demo', 'c', 'app', 'mail', 'smtp', 'imap', 'pop', 'ftp', 'ns1', 'ns2',
        'cdn', 'static', 'assets', 'status', 'root', 'mpoints', 'mpoint', 'login', 'painel', 'suporte',
        'help', 'blog', 'dev', 'test', 'staging', 'webmail', 'autodiscover', 'localhost',
    ];

    public static function normalizar(?string $slug): string
    {
        return strtolower(trim((string) $slug));
    }

    public static function valido(?string $slug): bool
    {
        $slug = (string) $slug;

        return preg_match(self::PADRAO, $slug) === 1
            && !str_contains($slug, '--')
            && !self::reservado($slug);
    }

    /** O link inteiro e o seu primeiro nível não podem ser nomes reservados. */
    public static function reservado(string $slug): bool
    {
        $primeiro = explode('.', $slug)[0];

        return in_array($slug, self::RESERVADOS, true) || in_array($primeiro, self::RESERVADOS, true);
    }

    /** Regras de validação Laravel; $ignorarId ao editar uma campanha existente. */
    public static function regras(bool $obrigatorio, ?int $ignorarId = null): array
    {
        return [
            $obrigatorio ? 'required' : 'sometimes',
            'string',
            'min:2',
            'max:' . self::MAX,
            'regex:' . self::PADRAO,
            'not_regex:/--/',
            function (string $atributo, $valor, \Closure $falhar) {
                if (is_string($valor) && self::reservado($valor)) {
                    $falhar('Este link está reservado pelo sistema. Escolha outro.');
                }
            },
            Rule::unique('campanha', 'slug')->ignore($ignorarId),
        ];
    }

    public static function mensagens(): array
    {
        return [
            'slug.required' => 'Indique o link da campanha.',
            'slug.min' => 'O link deve ter pelo menos 2 caracteres.',
            'slug.max' => 'O link pode ter no máximo 63 caracteres.',
            'slug.regex' => 'O link só pode ter letras minúsculas, números, hífens e pontos (até 3 níveis, ex.: mbottlestore.rifa), sem hífen ou ponto no início ou no fim de cada nível.',
            'slug.not_regex' => 'O link não pode ter dois hífens seguidos.',
            'slug.not_in' => 'Este link está reservado pelo sistema. Escolha outro.',
            'slug.unique' => 'Este link já está a ser usado por outra campanha.',
        ];
    }

    public static function link(?string $slug): ?string
    {
        $sufixo = config('campanhas.host_suffix');

        return $slug && $sufixo ? "https://{$slug}.{$sufixo}" : null;
    }

    public static function host(Campanha $campanha): ?string
    {
        $sufixo = config('campanhas.host_suffix');

        return $campanha->slug && $sufixo && self::valido($campanha->slug)
            ? "{$campanha->slug}.{$sufixo}"
            : null;
    }
}
