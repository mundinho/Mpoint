<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Usuario::all());
    }

    public function show(Usuario $usuario): JsonResponse
    {
        return response()->json($usuario);
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['required', 'string', 'max:20', 'unique:usuarios,telefone'],
        ]);

        $usuario = Usuario::create($dados);

        return response()->json($usuario, 201);
    }

    public function update(Request $request, Usuario $usuario): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['sometimes', 'string', 'max:255'],
            'telefone' => ['sometimes', 'string', 'max:20', 'unique:usuarios,telefone,' . $usuario->id],
        ]);

        $usuario->update($dados);

        return response()->json($usuario);
    }

    public function destroy(Usuario $usuario): JsonResponse
    {
        $usuario->delete();

        return response()->json(null, 204);
    }
}
