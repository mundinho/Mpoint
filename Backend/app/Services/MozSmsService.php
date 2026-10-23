<?php

namespace App\Services;

use App\Models\Administrador;
use App\Models\Sms;
use App\Models\Usuario;
use App\Support\DemoSms;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MozSmsService
{
    private string $baseUrl;
    private ?string $apiKey;
    private ?string $apiSecret;
    private string $senderId;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.mozesms.base_url'), '/');
        $this->apiKey = config('services.mozesms.api_key');
        $this->apiSecret = config('services.mozesms.api_secret');
        $this->senderId = config('services.mozesms.sender_id');
    }

    public function enviar(Usuario $usuario, string $tipo, string $mensagem): array
    {
        return $this->enviarBruto($usuario->telefone, $tipo, $mensagem, ['usuario_id' => $usuario->id]);
    }

    public function enviarParaAdmin(Administrador $administrador, string $tipo, string $mensagem): array
    {
        return $this->enviarBruto($administrador->telefone, $tipo, $mensagem, ['administrador_id' => $administrador->id]);
    }

    private function enviarBruto(string $telefone, string $tipo, string $mensagem, array $destinatario): array
    {
        // Demonstração: não envia nada, regista a mensagem como enviada e entrega-a ao frontend
        if (config('demo.enabled')) {
            Sms::create(array_merge($destinatario, [
                'tipo' => $tipo,
                'mesnagem' => $mensagem,
                'estado' => 'enviado',
                'enviado_em' => now(),
            ]));

            DemoSms::registar($telefone, $tipo, $mensagem);

            return ['simulado' => true];
        }

        if (!$this->apiKey || !$this->apiSecret) {
            throw new \RuntimeException('Credenciais da MozeSMS não configuradas (MOZESMS_API_KEY / MOZESMS_API_SECRET).');
        }

        $resposta = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'X-API-Secret' => $this->apiSecret,
        ])->post("{$this->baseUrl}/sms/send", [
            'phone' => $telefone,
            'message' => $mensagem,
            'sender_id' => $this->senderId,
        ]);

        $sucesso = $resposta->successful();

        Sms::create(array_merge($destinatario, [
            'tipo' => $tipo,
            'mesnagem' => $mensagem,
            'estado' => $sucesso ? 'enviado' : 'falhado',
            'enviado_em' => $sucesso ? now() : null,
        ]));

        if ($tipo !== 'alerta_saldo') {
            $this->verificarSaldo(!$sucesso);
        }

        if (!$sucesso) {
            throw new \RuntimeException('Falha ao enviar SMS: ' . ($resposta->json('error') ?? $resposta->body()));
        }

        return $resposta->json('data') ?? [];
    }

    /**
     * Saldo baixo: depois de cada envio do MPoints consulta as SMS que restam na conta
     * (no máximo de 5 em 5 minutos, ou logo após uma falha) e, quando chegam ao limite,
     * avisa os contactos de alerta uma única vez. O aviso volta a ficar disponível
     * quando a conta for recarregada acima do limite. Nunca interrompe o envio original.
     */
    private function verificarSaldo(bool $aposFalha): void
    {
        $contactos = config('services.mozesms.alerta_contactos', []);
        $limite = (int) config('services.mozesms.alerta_limite', 100);

        if (!$contactos || $limite <= 0) {
            return;
        }

        try {
            if (!$aposFalha && !Cache::add('mozesms:saldo_verificado', true, now()->addMinutes(5))) {
                return;
            }

            $resposta = Http::timeout(5)->withHeaders([
                'X-API-Key' => $this->apiKey,
                'X-API-Secret' => $this->apiSecret,
            ])->acceptJson()->get("{$this->baseUrl}/balance");

            $restantes = $resposta->json('data.estimated_sms_remaining') ?? $resposta->json('data.sms_credits');

            if (!$resposta->successful() || !is_numeric($restantes)) {
                return;
            }

            $restantes = (int) $restantes;

            if ($restantes > $limite) {
                Cache::forget('mozesms:alerta_saldo_enviado');
                return;
            }

            if (!Cache::add('mozesms:alerta_saldo_enviado', $restantes, now()->addYears(10))) {
                return;
            }

            Log::warning("MozeSMS: saldo baixo ({$restantes} SMS restantes). A avisar os contactos de alerta.");

            $mensagem = "MPoints: o saldo de SMS esta baixo, restam cerca de {$restantes} SMS. "
                . 'Recarregue a conta MozeSMS para os participantes continuarem a receber o codigo.';

            foreach ($contactos as $contacto) {
                try {
                    $this->enviarBruto($contacto, 'alerta_saldo', $mensagem, []);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
