<?php

namespace App\Http\Controllers;

use App\Models\Campanha;
use App\Services\CampanhaService;
use Illuminate\Http\JsonResponse;

class CampanhaController extends Controller
{
    public function __construct(private CampanhaService $campanhaService)
    {
    }

    public function ativa(): JsonResponse
    {
        $campanha = Campanha::ativa();

        return response()->json($campanha);
    }

    public function reset(): JsonResponse
    {
        $campanha = $this->campanhaService->resetOperacional();

        return response()->json($campanha, 201);
    }
}
