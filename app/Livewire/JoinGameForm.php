<?php

namespace App\Livewire;

use App\Events\TriggerRefresh;
use App\Models\Game;
use App\Models\Player;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class JoinGameForm extends Component
{
    public $gameCode = '';
    public $playerName = '';
    public $faction = '';

    // Opened through the game's rejoin link: pick your name instead of joining as a new player
    public $rejoin = false;
    // The name is taken, the person can ask to rejoin as that player
    public $nameTaken = false;
    // Status of a request to rejoin: waiting, declined or expired
    public $rejoinStatus = null;

    public function mount($gameCode = '', $rejoin = false)
    {
        $this->gameCode = strtoupper($gameCode);
        $this->rejoin = (bool) $rejoin;

        // Still waiting on a request made before reloading the page
        if ($this->rejoinRequestPlayer()?->hasPendingRejoinRequest()) {
            $this->rejoinStatus = 'waiting';
            $this->playerName = $this->rejoinRequestPlayer()->name;
        }
    }

    public function rules()
    {
        return [
            'gameCode' => 'required|string|size:6|exists:games,code',
            'playerName' => 'required|string|max:255',
            'faction' => 'nullable|string|max:255',
        ];
    }

    public function joinGame()
    {
        $throttleKey = 'join-game|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 30)) {
            $this->addError('gameCode', 'Too many attempts. Try again in ' . RateLimiter::availableIn($throttleKey) . ' seconds.');
            return;
        }
        RateLimiter::hit($throttleKey, 600);

        $this->validate();

        $game = Game::where('code', strtoupper($this->gameCode))->first();

        if (!$game) {
            $this->addError('gameCode', 'Game not found.');
            return;
        }

        if ($game->status === 'completed') {
            $this->addError('gameCode', 'This game has already ended.');
            return;
        }

        // Check if player name is already taken in this game
        if ($game->players()->where('name', $this->playerName)->exists()) {
            $this->addError('playerName', 'This name is already taken in this game.');
            $this->nameTaken = true;
            return;
        }

        $player = Player::create([
            'game_id' => $game->id,
            'name' => $this->playerName,
            'session_token' => Player::generateSessionToken(),
            'faction' => $this->faction,
            'is_speaker' => false,
        ]);

        Log::info('Player joined', ['game' => $game->code, 'player' => $player->name, 'faction' => $player->faction ?: null]);

        TriggerRefresh::dispatch($game);

        // Store session token in session
        session(['player_token' => $player->session_token]);

        return redirect()->route('game.show', $game->code);
    }

    /**
     * Ask the other players to confirm that this browser is the player with this name, e.g. after losing the session.
     */
    public function requestRejoin()
    {
        $throttleKey = 'rejoin-request|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('playerName', 'Too many rejoin requests. Try again in ' . ceil(RateLimiter::availableIn($throttleKey) / 60) . ' minutes.');
            return;
        }
        RateLimiter::hit($throttleKey, 600);

        $game = Game::where('code', strtoupper($this->gameCode))->first();
        $player = $game?->players()->where('name', $this->playerName)->first();
        if (!$player) {
            $this->addError('playerName', 'Choose your name.');
            return;
        }

        $token = Str::random(40);
        $player->update(['rejoin_request_token' => $token, 'rejoin_requested_at' => now(), 'rejoin_approved_at' => null]);
        session(['rejoin_request' => ['player' => $player->id, 'token' => $token]]);

        Log::info('Rejoin requested', ['game' => $game->code, 'player' => $player->name]);
        TriggerRefresh::dispatch($game);

        $this->resetErrorBag();
        $this->nameTaken = false;
        $this->rejoinStatus = 'waiting';
    }

    // Polled while waiting for another player to confirm the request
    public function checkRejoin()
    {
        if ($this->rejoinStatus !== 'waiting') {
            return;
        }

        $player = $this->rejoinRequestPlayer();

        if (!$player || $player->rejoin_request_token !== session('rejoin_request.token')) {
            // Declined, or replaced by a newer request
            session()->forget('rejoin_request');
            $this->rejoinStatus = 'declined';
            return;
        }

        if ($player->rejoin_approved_at) {
            $player->clearRejoinRequest();
            session()->forget('rejoin_request');
            session(['player_token' => $player->session_token]);
            Log::info('Player rejoined', ['game' => $player->game->code, 'player' => $player->name]);

            return redirect()->route('game.show', $player->game->code);
        }

        if (!$player->hasPendingRejoinRequest()) {
            $player->clearRejoinRequest();
            session()->forget('rejoin_request');
            $this->rejoinStatus = 'expired';
        }
    }

    public function cancelRejoin()
    {
        $player = $this->rejoinRequestPlayer();
        if ($player && $player->rejoin_request_token === session('rejoin_request.token')) {
            $player->clearRejoinRequest();
            TriggerRefresh::dispatch($player->game);
        }

        session()->forget('rejoin_request');
        $this->rejoinStatus = null;
    }

    private function rejoinRequestPlayer(): ?Player
    {
        $playerId = session('rejoin_request.player');

        return $playerId ? Player::find($playerId) : null;
    }

    public function render()
    {
        return view('livewire.join-game-form', [
            'gamePlayers' => $this->rejoin
                ? Game::where('code', $this->gameCode)->first()?->players()->orderBy('name')->pluck('name') ?? collect()
                : collect(),
        ]);
    }
}
