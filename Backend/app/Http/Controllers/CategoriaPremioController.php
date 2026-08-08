<?php

namespace App\Http\Controllers;

use App\Models\CategoriaPremio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaPremioController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(CategoriaPremio::orderBy('nome')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
        ]);

        $categoria = CategoriaPremio::create($dados + ['tipo' => 'normal']);

        return response()->json($categoria, 201);
    }

    public function update(Request $request, CategoriaPremio $categoria): JsonResponse
    {
        if ($categoria->tipo === 'tentar_novamente') {
            return response()->json(['message' => 'A categoria "Tentar Novamente" não pode ser editada.'], 422);
        }

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
        ]);

        $categoria->update($dados);

        return response()->json($categoria);
    }

    public function destroy(CategoriaPremio $categoria): JsonResponse
    {
        if ($categoria->tipo === 'tentar_novamente') {
            return response()->json(['message' => 'A categoria "Tentar Novamente" não pode ser removida.'], 422);
        }

        if ($categoria->premios()->exists()) {
            return response()->json(['message' => 'Não é possível remover: existem prémios associados a esta categoria.'], 422);
        }

        $categoria->delete();

        return response()->json(null, 204);
    }
}
