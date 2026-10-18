<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Log;

/**
 * Adds the game code, and the player name when known, to every log entry written while handling the component,
 * so the admin log viewer can filter on a game. Expects $game and optionally $player properties.
 */
trait SharesGameLogContext
{
    // Livewire calls this after mount on the first request and after hydrate on every later one
    public function bootedSharesGameLogContext(): void
    {
        if (!$this->game) {
            return;
        }

        Log::shareContext(array_filter([
            'game' => $this->game->code,
            'player' => $this->player?->name,
        ]));
    }
}
