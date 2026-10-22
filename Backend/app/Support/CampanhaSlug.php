<?php

namespace App\Support;

use App\Models\Campanha;
use Illuminate\Validation\Rule;

/**
 * Link (subdomínio) de uma campanha: {slug}.c.mpoints.site
 * Só letras minúsculas, números e hífens; 2 a 40 caracteres; sem hífen nas pontas.
 */
class CampanhaSlug
{
    public const PADRAO = '/^[a-z0-9](?:[a-z0-9-]{0,38}[a-z0-9])$/';

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
            && !in_array($slug, self::RESERVADOS, true);
    }

    /** Regras de validação Laravel; $ignorarId ao editar uma campanha existente. */
    public static function regras(bool $obrigatorio, ?int $ignorarId = null): array
    {
        return [
            $obrigatorio ? 'required' : 'sometimes',
            'string',
            'min:2',
            'max:40',
            'regex:' . self::PADRAO,
            'not_regex:/--/',
            Rule::notIn(self::RESERVADOS),
            Rule::unique('campanha', 'slug')->ignore($ignorarId),
        ];
    }

    public static function mensagens(): array
    {
        return [
            'slug.required' => 'Indique o link da campanha.',
            'slug.min' => 'O link deve ter pelo menos 2 caracteres.',
            'slug.max' => 'O link pode ter no máximo 40 caracteres.',
            'slug.regex' => 'O link só pode ter letras minúsculas, números e hífens, sem hífen no início ou no fim.',
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
