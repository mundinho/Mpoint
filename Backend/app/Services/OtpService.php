<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpService
{
    private const EXPIRACAO_MINUTOS = 5;
    private const LIMITE_TENTATIVAS = 3;
    private const REENVIO_INTERVALO_SEGUNDOS = 60;

    public function gerar(Usuario $usuario): Otp
    {
        $ultimo = $usuario->otps()->latest('id')->first();

        if ($ultimo && $ultimo->created_at->diffInSeconds(now()) < self::REENVIO_INTERVALO_SEGUNDOS) {
            throw new \RuntimeException('Aguarde antes de solicitar um novo código.');
        }

        $codigo = (string) random_int(100000, 999999);

        $otp = $usuario->otps()->create([
            'codigo_hash' => Hash::make($codigo),
            'expira_em' => now()->addMinutes(self::EXPIRACAO_MINUTOS),
            'tentativas' => 0,
            'validado_em' => null,
        ]);

        $otp->codigo_plano = $codigo;

        return $otp;
    }

    public function validar(Usuario $usuario, string $codigo): bool
    {
        $otp = $usuario->otps()->whereNull('validado_em')->latest('id')->first();

        if (!$otp) {
            throw new \RuntimeException('Nenhum código pendente.');
        }

        if ($otp->expirado()) {
            throw new \RuntimeException('Código expirado.');
        }

        if ($otp->tentativas >= self::LIMITE_TENTATIVAS) {
            throw new \RuntimeException('Limite de tentativas excedido.');
        }

        if (!Hash::check($codigo, $otp->codigo_hash)) {
            $otp->increment('tentativas');
            return false;
        }

        $otp->update(['validado_em' => now()]);
        $usuario->update(['telefone_verificado' => true]);

        return true;
    }
}
