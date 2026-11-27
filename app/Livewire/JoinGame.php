<?php

namespace App\Livewire;

use App\Models\Game;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class JoinGame extends Component
{
    public $gameCode = '';

    public function rules()
    {
        return [
            'gameCode' => 'required|string|size:6',
        ];
    }

    public function joinGame()
    {
        // Slows down guessing game codes
        $throttleKey = 'join-lookup|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 30)) {
            $this->addError('gameCode', 'Too many attempts. Try again in ' . ceil(RateLimiter::availableIn($throttleKey) / 60) . ' minutes.');
            return;
        }
        RateLimiter::hit($throttleKey, 600);

        $this->validate();

        $game = Game::where('code', strtoupper($this->gameCode))->first();

        if (!$game) {
            $this->addError('gameCode', 'Game not found. Please check the code and try again.');
            return;
        }

        if ($game->status === 'completed') {
            $this->addError('gameCode', 'This game has already ended.');
            return;
        }

        // Redirect to the join page for this specific game
        return redirect()->route('join-group', strtoupper($this->gameCode));
    }

    public function render()
    {
        return view('livewire.join-game');
    }
}
