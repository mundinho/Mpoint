<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuadradoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Campanha|null $campanha */
        $campanha = $request->attributes->get('campanha');

        if (!$campanha || !$campanha->estaAtiva()) {
            return response()->json(['message' => 'Esta campanha não está activa de momento.', 'codigo' => 'campanha_inactiva'], 422);
        }

        // Só número e estado: nunca expor que números têm prémio
        $quadrados = $campanha->quadrados()
            ->orderBy('numero')
            ->get(['id', 'numero', 'estado']);

        return response()->json($quadrados);
    }
}
