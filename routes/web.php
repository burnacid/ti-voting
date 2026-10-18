<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Game;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/create-game', function () {
    return view('create-game');
})->name('create-game');

Route::get('/join/{code}', function (string $code) {
    $game = Game::where('code', strtoupper($code))->first();

    if (!$game) {
        return redirect()->route('home')->with('error', 'Game not found.');
    }

    Log::shareContext(['game' => $game->code]);

    return view('join-game', ['code' => $code, 'game' => $game]);
})->name('join-group');

Route::get('/game/{code}', function (string $code) {
    $game = Game::where('code', strtoupper($code))->first();

    if (!$game) {
        return redirect()->route('home')->with('error', 'Game not found.');
    }

    Log::shareContext(['game' => $game->code]);

    // Check if player has a valid session for this game
    $playerToken = session('player_token');
    $player = null;

    if ($playerToken) {
        $player = $game->players()->where('session_token', $playerToken)->first();
    }

    if (!$player) {
        return redirect()->route('join-group', $code)->with('error', 'You need to join this game first.');
    }

    return view('game', ['game' => $game, 'player' => $player]);
})->name('game.show');

// Admin
Route::prefix('admin')->name('admin.')->group(function () {
    Route::view('/login', 'admin.login')->middleware('guest')->name('login');

    Route::middleware('auth')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::view('/games', 'admin.games')->name('games');
        Route::get('/games/{game}', fn (Game $game) => view('admin.game', ['game' => $game]))->name('games.show');
        Route::view('/logs', 'admin.logs')->name('logs');

        Route::post('/logout', function () {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('admin.login');
        })->name('logout');
    });
});
