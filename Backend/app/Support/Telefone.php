<?php

namespace App\Support;

class Telefone
{
    /**
     * Normaliza um número moçambicano para o formato 258XXXXXXXXX (12 dígitos),
     * aceitando entradas como "851935325", "+258851935325", "00258851935325"
     * ou já normalizadas.
     */
    public static function normalizar(?string $telefone): string
    {
        $digitos = preg_replace('/\D/', '', (string) $telefone);

        if (str_starts_with($digitos, '00258')) {
            $digitos = substr($digitos, 2);
        }

        if (strlen($digitos) === 9 && str_starts_with($digitos, '8')) {
            $digitos = '258' . $digitos;
        }

        if (!preg_match('/^258[8][2-7][0-9]{7}$/', $digitos)) {
            throw new \InvalidArgumentException('Número de telefone inválido. Use o formato 8XXXXXXXX (ex: 851935325).');
        }

        return $digitos;
    }
}
