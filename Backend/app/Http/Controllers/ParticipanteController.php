<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParticipanteController extends Controller
{
    public function __construct(private OtpService $otpService, private AuditoriaService $auditoria)
    {
    }

    public function registar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['required', 'string', 'max:20'],
        ]);

        $usuario = Usuario::where('telefone', $dados['telefone'])->first();

        if ($usuario) {
            $campanha = Campanha::ativa();

            if ($campanha && $campanha->participacoes()->where('usuario_id', $usuario->id)->exists()) {
                return response()->json(['message' => 'Este telefone já participou no ciclo actual.'], 422);
            }

            $usuario->update(['nome' => $dados['nome'], 'telefone_verificado' => false]);
        } else {
            $usuario = Usuario::create($dados + ['telefone_verificado' => false]);
        }

        $this->auditoria->registrar('Usuario', 'registo', true, "Participante registado/actualizado: {$usuario->telefone}");

        try {
            $otp = $this->otpService->gerar($usuario);
        } catch (\RuntimeException $e) {
            return response()->json([
                'usuario' => $usuario,
                'message' => 'Participante registado, mas houve falha ao enviar o SMS com o código. Use /otp/reenviar para tentar novamente.',
            ], 502);
        }

        return response()->json([
            'usuario' => $usuario,
            'otp_expira_em' => $otp->expira_em,
        ], 201);
    }
}
