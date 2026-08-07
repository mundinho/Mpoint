<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use Illuminate\Http\JsonResponse;

class QuadradoController extends Controller
{
    public function index(): JsonResponse
    {
        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
        }

        $quadrados = $campanha->quadrados()
            ->orderBy('numero')
            ->get(['id', 'numero', 'estado']);

        return response()->json($quadrados);
    }
}
