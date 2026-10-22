<?php

namespace App\Services;

use App\Models\Campanha;
use App\Models\Otp;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpService
{
    private const EXPIRACAO_MINUTOS_DEFAULT = 5;
    private const LIMITE_TENTATIVAS = 3;
    private const REENVIO_INTERVALO_SEGUNDOS = 60;

    public function __construct(
        private MozSmsService $smsService,
        private AuditoriaService $auditoria
    ) {
    }

    /**
     * Gera e envia um código para o participante, associado à campanha em que se registou.
     */
    public function gerar(Usuario $usuario, ?Campanha $campanha): Otp
    {
        $ultimo = $usuario->otps()->latest('id')->first();

        if ($ultimo && $ultimo->created_at->diffInSeconds(now()) < self::REENVIO_INTERVALO_SEGUNDOS) {
            throw new \RuntimeException('Aguarde antes de solicitar um novo código.');
        }

        $codigo = (string) random_int(100000, 999999);
        $validadeMinutos = $campanha?->otp_validade_minutos ?? self::EXPIRACAO_MINUTOS_DEFAULT;

        $otp = $usuario->otps()->create([
            'campanha_id' => $campanha?->id,
            'codigo_hash' => Hash::make($codigo),
            'expira_em' => now()->addMinutes($validadeMinutos),
            'tentativas' => 0,
            'validado_em' => null,
        ]);

        $this->smsService->enviar($usuario, 'otp', "O seu código MPoint é {$codigo}. Válido por {$validadeMinutos} minutos.");

        $otp->codigo_plano = $codigo;

        return $otp;
    }

    /**
     * Valida o código mais recente pendente desta campanha. Em caso de sucesso devolve
     * um token de participação (só o hash fica guardado); com código errado devolve null.
     */
    public function validar(Usuario $usuario, string $codigo, ?Campanha $campanha): ?string
    {
        $otp = $usuario->otps()
            ->whereNull('validado_em')
            ->where('campanha_id', $campanha?->id)
            ->latest('id')
            ->first();

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
            return null;
        }

        $token = Str::random(48);

        $otp->update([
            'validado_em' => now(),
            'token_hash' => hash('sha256', $token),
            'token_expira_em' => now()->addMinutes(config('campanhas.token_minutos', 30)),
        ]);
        $usuario->update(['telefone_verificado' => true]);

        $this->auditoria->registrar('Usuario', 'otp_validado', true, "Telefone {$usuario->telefone} validado (usuario {$usuario->id}, campanha " . ($campanha?->id ?? '-') . ').');

        return $token;
    }

    /**
     * Confirma que o token pertence a este participante, nesta campanha, e não expirou.
     */
    public function tokenValido(Usuario $usuario, ?Campanha $campanha, ?string $token): bool
    {
        if (!$campanha || !$token || strlen($token) > 128) {
            return false;
        }

        return Otp::where('usuario_id', $usuario->id)
            ->where('campanha_id', $campanha->id)
            ->where('token_hash', hash('sha256', $token))
            ->where('token_expira_em', '>', now())
            ->exists();
    }
}
