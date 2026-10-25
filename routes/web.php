<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Game;
use App\Support\PlayerGames;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // The installed web app always opens here, so offer the games this browser plays in
    return view('welcome', ['myPlayers' => PlayerGames::players()]);
})->name('home');

// Removes a game from this browser's list of games. The player stays in the game
Route::post('/my-games/{code}/forget', function (string $code) {
    $code = strtoupper($code);
    PlayerGames::forget($code);

    // Before the games cookie existed the player was kept in the session, which would bring the game back
    $game = Game::where('code', $code)->first();
    if ($game && session('player_token') && $game->players()->where('session_token', session('player_token'))->exists()) {
        session()->forget('player_token');
    }

    return redirect()->route('home');
})->name('my-games.forget');

Route::view('/privacy', 'privacy')->name('privacy');

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

    $player = PlayerGames::playerFor($game);

    if (!$player) {
        return redirect()->route('join-group', $code)->with('error', 'You need to join this game first.');
    }

    // Keeps the game at the top of this browser's games and renews the cookie. Not for an admin opening it as a player
    if (!PlayerGames::impersonated($game)) {
        PlayerGames::remember($player);
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

// Admin login with a passkey, at /admin/passkeys/... The package's views expect the route names passkeys.*
Route::prefix(config('app.admin_path'))->middleware(['guest', 'throttle:20,1'])->group(fn () => Route::passkeys());

// Admin
Route::prefix(config('app.admin_path'))->name('admin.')->group(function () {
    Route::view('/login', 'admin.login')->middleware('guest')->name('login');
    // Second step of the login when TOTP is on
    Route::view('/two-factor', 'admin.two-factor')->middleware('guest')->name('two-factor');

    Route::middleware('auth')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::view('/games', 'admin.games')->name('games');
        Route::get('/games/{game}', fn (Game $game) => view('admin.game', ['game' => $game]))->name('games.show');

        // Open a game as one of its players, e.g. to help a player or fix a game
        Route::post('/games/{game}/impersonate/{player}', function (Game $game, string $player) {
            $player = $game->players()->findOrFail($player);

            PlayerGames::impersonate($player);
            Log::warning('Admin opened a game as a player', ['game' => $game->code, 'player' => $player->name, 'admin' => Auth::user()->email]);

            return redirect()->route('game.show', $game->code);
        })->name('games.impersonate');

        Route::post('/impersonate/stop', function () {
            $game = Game::where('code', session('impersonate.game'))->first();
            PlayerGames::stopImpersonating();

            return $game ? redirect()->route('admin.games.show', $game) : redirect()->route('admin.games');
        })->name('impersonate.stop');
        Route::view('/logs', 'admin.logs')->name('logs');
        Route::view('/security', 'admin.security')->name('security');
        Route::get('/feedback', function () {
            abort_unless(config('app.feedback'), 404);

            return view('admin.feedback');
        })->name('feedback');

        Route::post('/logout', function () {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('admin.login');
        })->name('logout');
    });
});
