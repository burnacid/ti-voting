<?php

namespace App\Livewire\Admin;

use App\Models\Game;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class GameDetail extends Component
{
    public Game $game;

    public function deleteGame()
    {
        // Players, agendas and votes are deleted with the game
        $this->game->delete();
        Log::info('Admin deleted a game', ['game' => $this->game->code, 'name' => $this->game->name, 'admin' => Auth::user()->email]);

        session()->flash('status', "Game {$this->game->name} ({$this->game->code}) was deleted.");

        return $this->redirectRoute('admin.games');
    }

    public function render()
    {
        return view('livewire.admin.game-detail', [
            'players' => $this->game->players()->orderBy('name')->get(),
            'agendas' => $this->game->agendas()->with('votes.player')->latest()->get(),
        ]);
    }
}
