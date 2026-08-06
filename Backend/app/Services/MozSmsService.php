<?php

namespace App\Services;

use App\Models\Sms;
use App\Models\Usuario;
use Illuminate\Support\Facades\Http;

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
        if (!$this->apiKey || !$this->apiSecret) {
            throw new \RuntimeException('Credenciais da MozeSMS não configuradas (MOZESMS_API_KEY / MOZESMS_API_SECRET).');
        }

        $resposta = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'X-API-Secret' => $this->apiSecret,
        ])->post("{$this->baseUrl}/sms/send", [
            'phone' => $usuario->telefone,
            'message' => $mensagem,
            'sender_id' => $this->senderId,
        ]);

        $sucesso = $resposta->successful();

        Sms::create([
            'usuario_id' => $usuario->id,
            'tipo' => $tipo,
            'mesnagem' => $mensagem,
            'estado' => $sucesso ? 'enviado' : 'falhado',
            'enviado_em' => $sucesso ? now() : null,
        ]);

        if (!$sucesso) {
            throw new \RuntimeException('Falha ao enviar SMS: ' . ($resposta->json('error') ?? $resposta->body()));
        }

        return $resposta->json('data') ?? [];
    }
}
