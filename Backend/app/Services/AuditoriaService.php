<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Support\Facades\Request as RequestFacade;

class AuditoriaService
{
    public function registrar(string $classe, string $acao, bool $sucesso, string $descricao): Log
    {
        $anterior = Log::latest('id')->first();
        $previosHash = $anterior->hash ?? str_repeat('0', 64);
        $timestamp = now()->format('Y-m-d H:i:s');

        $hash = hash('sha256', $previosHash . $classe . $acao . ($sucesso ? '1' : '0') . $descricao . $timestamp);

        return Log::create([
            'hash' => $hash,
            'previos_hash' => $previosHash,
            'class' => $classe,
            'action' => $acao,
            'success' => $sucesso,
            'timestamp' => $timestamp,
            'description' => $descricao,
            'device_id' => RequestFacade::header('X-Device-Id'),
            'device_ip' => RequestFacade::ip(),
        ]);
    }
}
