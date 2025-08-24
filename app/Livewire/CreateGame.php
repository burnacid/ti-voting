<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Player;
use Livewire\Component;

class CreateGame extends Component
{
    public $gameName = '';
    public $playerName = '';
    public $faction = '';

    public function rules()
    {
        return [
            'gameName' => 'required|string|max:255',
            'playerName' => 'required|string|max:255',
            'faction' => 'nullable|string|max:255',
        ];
    }

    public function createGame()
    {
        $this->validate();

        $game = Game::create([
            'code' => Game::generateCode(),
            'name' => $this->gameName,
            'status' => 'waiting',
        ]);

        $player = Player::create([
            'game_id' => $game->id,
            'name' => $this->playerName,
            'session_token' => Player::generateSessionToken(),
            'faction' => $this->faction,
            'is_speaker' => true, // First player is the speaker
        ]);

        // Update game with speaker
        $game->update(['speaker_id' => $player->id]);

        // Store session token in session
        session(['player_token' => $player->session_token]);

        return redirect()->route('game.show', $game->code);
    }

    public function render()
    {
        return view('livewire.create-game');
    }
}
