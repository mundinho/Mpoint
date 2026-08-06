<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParticipanteController extends Controller
{
    public function __construct(private OtpService $otpService)
    {
    }

    public function registar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['required', 'string', 'max:20', 'unique:usuarios,telefone'],
        ]);

        $usuario = Usuario::create($dados + ['telefone_verificado' => false]);

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
