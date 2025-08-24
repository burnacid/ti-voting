<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Player;
use Livewire\Component;

class JoinGameForm extends Component
{
    public $gameCode = '';
    public $playerName = '';
    public $faction = '';

    public function mount($gameCode = '')
    {
        $this->gameCode = strtoupper($gameCode);
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
            return;
        }

        $player = Player::create([
            'game_id' => $game->id,
            'name' => $this->playerName,
            'session_token' => Player::generateSessionToken(),
            'faction' => $this->faction,
            'is_speaker' => false,
        ]);

        // Store session token in session
        session(['player_token' => $player->session_token]);

        return redirect()->route('game.show', $game->code);
    }

    public function render()
    {
        return view('livewire.join-game-form');
    }
}
