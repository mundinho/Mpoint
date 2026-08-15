<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\CampanhaController;
use App\Http\Controllers\CampanhaPremioController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\ParticipacaoController;
use App\Http\Controllers\ParticipanteController;
use App\Http\Controllers\PremioBancoController;
use App\Http\Controllers\PremioController;
use App\Http\Controllers\QuadradoController;
use App\Http\Controllers\SorteioController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::post('participantes/registar', [ParticipanteController::class, 'registar']);

Route::post('otp/reenviar', [OtpController::class, 'reenviar']);
Route::post('otp/validar', [OtpController::class, 'validar']);

Route::post('sorteio/abrir', [SorteioController::class, 'abrir']);

Route::get('campanha/ativa', [CampanhaController::class, 'ativa']);

Route::get('quadrados', [QuadradoController::class, 'index']);

Route::get('participacoes/{usuarioId}/resultado', [ParticipacaoController::class, 'resultado']);

Route::post('admin/login/solicitar', [AdminAuthController::class, 'solicitarLogin']);
Route::post('admin/login/validar', [AdminAuthController::class, 'validarLogin']);

Route::middleware('admin.auth')->group(function () {
    Route::get('admin/me', [AdminAuthController::class, 'me']);
    Route::post('admin/logout', [AdminAuthController::class, 'logout']);

    Route::post('admin/participantes/conceder-tentativa', [AdminDashboardController::class, 'concederTentativa']);

    Route::apiResource('usuarios', UsuarioController::class);

    Route::get('admin/campanhas', [CampanhaController::class, 'index']);
    Route::get('admin/campanhas/{campanha}', [CampanhaController::class, 'mostrar']);

    Route::get('admin/campanhas/{campanha}/estatisticas', [AdminDashboardController::class, 'estatisticas']);
    Route::get('admin/campanhas/{campanha}/relatorios', [AdminDashboardController::class, 'relatorios']);
    Route::get('admin/campanhas/{campanha}/participantes', [AdminDashboardController::class, 'participantes']);
    Route::get('admin/campanhas/{campanha}/vencedores', [AdminDashboardController::class, 'vencedores']);
    Route::get('admin/campanhas/{campanha}/atividade', [AdminDashboardController::class, 'atividade']);

    Route::get('admin/campanhas/{campanha}/premios/resumo', [PremioController::class, 'resumo']);
    Route::put('admin/campanhas/{campanha}/premios/{numero}', [PremioController::class, 'update']);

    // Prémios da campanha (novo modelo — cada prémio tem o seu próprio modo, manual
    // ou aleatório, coexistindo na mesma campanha). Preferir estes aos endpoints
    // legados de distribuição em bloco abaixo.
    Route::get('admin/campanhas/{campanha}/premios-campanha', [CampanhaPremioController::class, 'index']);
    Route::post('admin/campanhas/{campanha}/premios-campanha', [CampanhaPremioController::class, 'store']);
    Route::put('admin/campanhas/{campanha}/premios-campanha/{campanhaPremio}', [CampanhaPremioController::class, 'update']);
    Route::delete('admin/campanhas/{campanha}/premios-campanha/{campanhaPremio}', [CampanhaPremioController::class, 'destroy']);

    Route::get('admin/premios-banco', [PremioBancoController::class, 'index']);
    Route::post('admin/premios-banco', [PremioBancoController::class, 'store']);
    Route::put('admin/premios-banco/{premioBanco}', [PremioBancoController::class, 'update']);
    Route::delete('admin/premios-banco/{premioBanco}', [PremioBancoController::class, 'destroy']);

    Route::post('campanha/reset', [CampanhaController::class, 'reset']);
    Route::put('campanha/{campanha}', [CampanhaController::class, 'atualizar']);
    Route::post('campanha/{campanha}/activar', [CampanhaController::class, 'activar']);
    Route::post('campanha/{campanha}/pausar', [CampanhaController::class, 'pausar']);
    Route::post('campanha/{campanha}/encerrar', [CampanhaController::class, 'encerrar']);
    // Deprecated: substituem TODOS os prémios da campanha por um único modo (não
    // suportam manual+aleatório coexistindo). Mantidos só por compatibilidade —
    // preferir admin/campanhas/{campanha}/premios-campanha acima.
    Route::put('campanha/{campanha}/distribuicao/aleatorio', [CampanhaController::class, 'distribuicaoAleatoria']);
    Route::put('campanha/{campanha}/distribuicao/manual', [CampanhaController::class, 'distribuicaoManual']);
});
