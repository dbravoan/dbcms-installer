<?php

declare(strict_types=1);

use DavidBravo\ClientArea\Http\AccountController;
use DavidBravo\ClientArea\Http\GamesController;
use DavidBravo\ClientArea\Http\RequirePlayer;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('zona-juegos')->name('client-area.')->group(function (): void {
    Route::get('/registro', [AccountController::class, 'registration'])->name('register');
    Route::post('/registro', [AccountController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/entrar', [AccountController::class, 'loginForm'])->name('login');
    Route::post('/entrar', [AccountController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(RequirePlayer::class)->group(function (): void {
        Route::get('/', [GamesController::class, 'index'])->name('games');
        Route::get('/juegos/{game}', [GamesController::class, 'show'])->name('game');
        Route::post('/salir', [AccountController::class, 'logout'])->name('logout');
    });
});
