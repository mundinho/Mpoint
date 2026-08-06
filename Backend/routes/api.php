<?php

use App\Http\Controllers\CampanhaController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\ParticipanteController;
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
