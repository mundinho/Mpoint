<?php

namespace App\Services;

use App\Models\Administrador;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAuthService
{
    private const EXPIRACAO_MINUTOS = 5;
    private const LIMITE_TENTATIVAS = 3;
    private const REENVIO_INTERVALO_SEGUNDOS = 60;

    public function __construct(private MozSmsService $smsService, private AuditoriaService $auditoria)
    {
    }

    public function solicitarLogin(string $telefone): Administrador
    {
        $administrador = Administrador::where('telefone', $telefone)->where('ativo', true)->first();

        if (!$administrador) {
            throw new \RuntimeException('Administrador não encontrado ou inactivo.');
        }

        $ultimo = $administrador->otps()->latest('id')->first();

        if ($ultimo && $ultimo->created_at->diffInSeconds(now()) < self::REENVIO_INTERVALO_SEGUNDOS) {
            throw new \RuntimeException('Aguarde antes de solicitar um novo código.');
        }

        $codigo = (string) random_int(100000, 999999);

        $administrador->otps()->create([
            'codigo_hash' => Hash::make($codigo),
            'expira_em' => now()->addMinutes(self::EXPIRACAO_MINUTOS),
            'tentativas' => 0,
            'validado_em' => null,
        ]);

        $this->smsService->enviarParaAdmin($administrador, 'admin_otp', "O seu código de acesso MPoint Admin é {$codigo}. Válido por " . self::EXPIRACAO_MINUTOS . ' minutos.');

        return $administrador;
    }

    public function validarLogin(string $telefone, string $codigo): string
    {
        $administrador = Administrador::where('telefone', $telefone)->where('ativo', true)->first();

        if (!$administrador) {
            throw new \RuntimeException('Administrador não encontrado ou inactivo.');
        }

        $otp = $administrador->otps()->whereNull('validado_em')->latest('id')->first();

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
            $this->auditoria->registrar('Administrador', 'login_falhado', false, "Tentativa de login falhada para admin {$administrador->id}.");
            throw new \RuntimeException('Código incorrecto.');
        }

        $otp->update(['validado_em' => now()]);

        $token = Str::random(64);
        $administrador->update([
            'api_token' => hash('sha256', $token),
            'token_expira_em' => now()->addMinutes(Administrador::SESSION_MINUTES),
        ]);

        $this->auditoria->registrar('Administrador', 'login', true, "Admin {$administrador->id} ({$administrador->nome}) autenticado.");

        return $token;
    }

    public function logout(Administrador $administrador): void
    {
        $administrador->update(['api_token' => null, 'token_expira_em' => null]);
    }
}
