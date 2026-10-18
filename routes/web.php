<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Game;
use App\Models\Player;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // The installed web app always opens here, so offer the game this browser is playing
    $currentPlayer = session('player_token')
        ? Player::with('game')->where('session_token', session('player_token'))->first()
        : null;

    return view('welcome', ['currentPlayer' => $currentPlayer]);
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

// For players who lost their session: pick your name, another player confirms it is you
Route::get('/game/{code}/rejoin', function (string $code) {
    $game = Game::where('code', strtoupper($code))->first();

    if (!$game) {
        return redirect()->route('home')->with('error', 'Game not found.');
    }

    Log::shareContext(['game' => $game->code]);

    return view('join-game', ['code' => $game->code, 'game' => $game, 'rejoin' => true]);
})->name('game.rejoin');

// Admin
Route::prefix(config('app.admin_path'))->name('admin.')->group(function () {
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
