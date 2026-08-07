<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function __construct(private OtpService $otpService)
    {
    }

    public function reenviar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
        ]);

        $usuario = Usuario::findOrFail($dados['usuario_id']);

        try {
            $otp = $this->otpService->gerar($usuario);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        }

        return response()->json(['otp_expira_em' => $otp->expira_em]);
    }

    public function validar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'codigo' => ['required', 'string', 'size:6'],
        ]);

        $usuario = Usuario::findOrFail($dados['usuario_id']);

        try {
            $valido = $this->otpService->validar($usuario, $dados['codigo']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (!$valido) {
            return response()->json(['message' => 'Código incorrecto.'], 422);
        }

        return response()->json(['message' => 'Telefone validado com sucesso.']);
    }
}
