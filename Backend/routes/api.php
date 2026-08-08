<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\CampanhaController;
use App\Http\Controllers\CategoriaPremioController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\ParticipacaoController;
use App\Http\Controllers\ParticipanteController;
use App\Http\Controllers\PremioController;
use App\Http\Controllers\QuadradoController;
use App\Http\Controllers\SorteioController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::apiResource('usuarios', UsuarioController::class);

Route::post('participantes/registar', [ParticipanteController::class, 'registar']);

Route::post('otp/reenviar', [OtpController::class, 'reenviar']);
Route::post('otp/validar', [OtpController::class, 'validar']);

Route::post('sorteio/abrir', [SorteioController::class, 'abrir']);

Route::get('campanha/ativa', [CampanhaController::class, 'ativa']);
Route::post('campanha/reset', [CampanhaController::class, 'reset']);
Route::post('campanha/premios', [CampanhaController::class, 'definirPremios']);
Route::put('campanha/{campanha}', [CampanhaController::class, 'atualizar']);
Route::post('campanha/{campanha}/activar', [CampanhaController::class, 'activar']);
Route::post('campanha/{campanha}/pausar', [CampanhaController::class, 'pausar']);
Route::post('campanha/{campanha}/encerrar', [CampanhaController::class, 'encerrar']);

Route::get('quadrados', [QuadradoController::class, 'index']);

Route::get('premios', [PremioController::class, 'index']);
Route::post('premios', [PremioController::class, 'store']);
Route::put('premios/{numero}', [PremioController::class, 'update']);
Route::delete('premios/{numero}', [PremioController::class, 'destroy']);

Route::get('participacoes/{usuarioId}/resultado', [ParticipacaoController::class, 'resultado']);

Route::post('admin/login/solicitar', [AdminAuthController::class, 'solicitarLogin']);
Route::post('admin/login/validar', [AdminAuthController::class, 'validarLogin']);

Route::middleware('admin.auth')->group(function () {
    Route::get('admin/me', [AdminAuthController::class, 'me']);
    Route::post('admin/logout', [AdminAuthController::class, 'logout']);

    Route::get('admin/dashboard/estatisticas', [AdminDashboardController::class, 'estatisticas']);
    Route::get('admin/dashboard/atividade', [AdminDashboardController::class, 'atividadeRecente']);
    Route::get('admin/dashboard/relatorios', [AdminDashboardController::class, 'relatorios']);
    Route::get('admin/participantes', [AdminDashboardController::class, 'participantes']);
    Route::post('admin/participantes/conceder-tentativa', [AdminDashboardController::class, 'concederTentativa']);
    Route::get('admin/vencedores', [AdminDashboardController::class, 'vencedores']);
<<<<<<< HEAD
    Route::get('admin/premios/resumo', [PremioController::class, 'resumo']);

    Route::put('campanha/{campanha}/distribuicao/manual', [CampanhaController::class, 'configurarDistribuicaoManual']);
    Route::put('campanha/{campanha}/distribuicao/aleatorio', [CampanhaController::class, 'configurarDistribuicaoAleatoria']);
=======

    Route::get('admin/categorias-premio', [CategoriaPremioController::class, 'index']);
    Route::post('admin/categorias-premio', [CategoriaPremioController::class, 'store']);
    Route::put('admin/categorias-premio/{categoria}', [CategoriaPremioController::class, 'update']);
    Route::delete('admin/categorias-premio/{categoria}', [CategoriaPremioController::class, 'destroy']);

    Route::put('campanha/{campanha}/distribuicao/aleatorio', [CampanhaController::class, 'distribuicaoAleatoria']);
    Route::put('campanha/{campanha}/distribuicao/manual', [CampanhaController::class, 'distribuicaoManual']);

    Route::post('admin/participantes/conceder-tentativa', [AdminDashboardController::class, 'concederTentativa']);
    Route::get('admin/dashboard/atividade', [AdminDashboardController::class, 'atividade']);
>>>>>>> 318b0efbcc92ff9a31ee160f7e2209f82ee66809
});
