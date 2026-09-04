<?php

use App\Http\Controllers\Agent\AgentTestController;
use App\Http\Controllers\WhatsAppEmbeddedSignupController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/politica-de-privacidad', function () {
    return view('legal.privacy-policy');
})->name('legal.privacy-policy');

Route::middleware('auth')->prefix('test')->group(function () {
    Route::get('/clientes', [AgentTestController::class, 'clientes']);
    Route::post('/mensaje', [AgentTestController::class, 'mensaje']);
});

Route::middleware('auth')->post(
    '/whatsapp/embedded-signup/{tenant}/callback',
    [WhatsAppEmbeddedSignupController::class, 'callback']
)->name('whatsapp.embedded-signup.callback');
