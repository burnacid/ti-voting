<?php

namespace App\Livewire\Admin;

use App\Events\TriggerRefresh;
use App\Models\Game;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class GameDetail extends Component
{
    public Game $game;

    // For when the Speaker lost their session and nobody can run the agendas
    public function makeSpeaker($playerId)
    {
        $player = $this->game->players()->find($playerId);
        if (!$player) {
            return;
        }

        $this->game->setSpeaker($player);
        Log::info('Admin made a player Speaker', ['game' => $this->game->code, 'player' => $player->name, 'admin' => Auth::user()->email]);
        TriggerRefresh::dispatch($this->game);
    }

    public function removePlayer($playerId)
    {
        $player = $this->game->players()->find($playerId);
        if (!$player) {
            return;
        }

        $this->game->removePlayer($player);
        Log::info('Admin removed a player', ['game' => $this->game->code, 'removed' => $player->name, 'admin' => Auth::user()->email]);
        TriggerRefresh::dispatch($this->game);
    }

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
