<?php

use App\Http\Controllers\CampanhaController;
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

Route::get('quadrados', [QuadradoController::class, 'index']);
Route::get('premios', [PremioController::class, 'index']);
Route::get('participacoes/{usuarioId}/resultado', [ParticipacaoController::class, 'resultado']);
