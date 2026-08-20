<?php

namespace App\Http\Controllers;

use App\Models\PremioBanco;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'descricao' => ['nullable', 'string', 'max:255'],
            'quantidade_padrao' => ['sometimes', 'integer', 'min:1'],
        ]);

        // A busca usa uma query directa (não passa pelo mutator do modelo), por isso
        // normaliza aqui manualmente para bater com o que já está guardado.
        $premioBanco = PremioBanco::firstOrCreate(
            ['nome' => Str::upper(trim($dados['nome']))],
            [
                'descricao' => $dados['descricao'] ?? null,
                'quantidade_padrao' => $dados['quantidade_padrao'] ?? 1,
            ]
        );

        return response()->json($premioBanco, 201);
    }

    public function update(Request $request, PremioBanco $premioBanco): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['sometimes', 'string', 'max:255'],
            'descricao' => ['sometimes', 'nullable', 'string', 'max:255'],
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
