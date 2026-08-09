<?php

namespace App\Http\Controllers;

use App\Models\PremioBanco;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PremioBancoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(PremioBanco::orderBy('nome')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'quantidade_padrao' => ['sometimes', 'integer', 'min:1'],
        ]);

        $premioBanco = PremioBanco::firstOrCreate(
            ['nome' => $dados['nome']],
            ['quantidade_padrao' => $dados['quantidade_padrao'] ?? 1]
        );

        return response()->json($premioBanco, 201);
    }

    public function update(Request $request, PremioBanco $premioBanco): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['sometimes', 'string', 'max:255'],
            'quantidade_padrao' => ['sometimes', 'integer', 'min:1'],
        ]);

        $premioBanco->update($dados);

        return response()->json($premioBanco);
    }

    public function destroy(PremioBanco $premioBanco): JsonResponse
    {
        $premioBanco->delete();

        return response()->json(null, 204);
    }
}
