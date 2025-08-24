<?php

use Illuminate\Support\Facades\Route;
use App\Models\Game;

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

    return view('join-game', ['code' => $code, 'game' => $game]);
})->name('join-group');

Route::get('/game/{code}', function (string $code) {
    $game = Game::where('code', strtoupper($code))->first();

    if (!$game) {
        return redirect()->route('home')->with('error', 'Game not found.');
    }

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

// Alternative route for backward compatibility
Route::get('/group/{group}', function (App\Models\Group $group) {
    return view('group', ['group' => $group]);
})->name('group.show');
