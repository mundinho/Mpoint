<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use Illuminate\Http\JsonResponse;

class PremioController extends Controller
{
    public function index(): JsonResponse
    {
        $campanha = Campanha::ativa();

        if (!$campanha) {
            return response()->json(['message' => 'Não existe campanha activa.'], 422);
        }

        return response()->json($campanha->premios()->get(['id', 'descricao', 'valor_estimado']));
    }
}
