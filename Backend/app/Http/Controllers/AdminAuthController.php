<?php

namespace App\Http\Controllers;

use App\Services\AdminAuthService;
use App\Support\Telefone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuthController extends Controller
{
    public function __construct(private AdminAuthService $adminAuthService)
    {
    }

    public function solicitarLogin(Request $request): JsonResponse
    {
        try {
            $request->merge(['telefone' => Telefone::normalizar($request->input('telefone'))]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $dados = $request->validate([
            'telefone' => ['required', 'string', 'max:20'],
        ]);

        try {
            $this->adminAuthService->solicitarLogin($dados['telefone']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Código enviado.']);
    }

    public function validarLogin(Request $request): JsonResponse
    {
        try {
            $request->merge(['telefone' => Telefone::normalizar($request->input('telefone'))]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $dados = $request->validate([
            'telefone' => ['required', 'string', 'max:20'],
            'codigo' => ['required', 'string', 'size:6'],
        ]);

        try {
            $token = $this->adminAuthService->validarLogin($dados['telefone'], $dados['codigo']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['token' => $token]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->adminAuthService->logout($request->attributes->get('administrador'));

        return response()->json(['message' => 'Sessão terminada.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->attributes->get('administrador'));
    }
}
