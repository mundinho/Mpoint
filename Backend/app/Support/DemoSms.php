<?php

namespace App\Support;

/**
 * Caixa de saída dos SMS simulados no modo de demonstração.
 * Vive apenas durante o pedido: o middleware AppendDemoSms junta-a à resposta.
 */
class DemoSms
{
    /** @var array<int, array{telefone:string, tipo:string, mensagem:string, codigo:?string}> */
    private static array $mensagens = [];

    public static function registar(string $telefone, string $tipo, string $mensagem): void
    {
        preg_match('/\b(\d{6})\b/', $mensagem, $codigo);

        self::$mensagens[] = [
            'telefone' => $telefone,
            'tipo' => $tipo,
            'mensagem' => $mensagem,
            'codigo' => in_array($tipo, ['otp', 'admin_otp'], true) ? ($codigo[1] ?? null) : null,
        ];
    }

    public static function retirar(): array
    {
        $mensagens = self::$mensagens;
        self::$mensagens = [];

        return $mensagens;
    }
}
