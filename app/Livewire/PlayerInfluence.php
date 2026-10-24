<?php

namespace App\Livewire;

use App\Events\TriggerRefresh;
use App\Livewire\Concerns\SharesGameLogContext;
use App\Models\Player;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The influence a player has available, entered by the player in the game header and shown to every player.
 */
class PlayerInfluence extends Component
{
    use SharesGameLogContext;

    public $game;

    // Kept as an id and loaded on every request, as the Speaker can remove the player mid-game
    #[Locked]
    public string $playerId;
    protected ?Player $player = null;
    public $influence = '';

    public function mount($game, $player): void
    {
        $this->game = $game;
        $this->playerId = $player->id;
        $this->player = $player;
        $this->influence = $player->influence ?? '';
    }

    public function hydrate(): void
    {
        $this->player = Player::find($this->playerId);
    }

    // Empty clears it
    public function save(): void
    {
        if (!$this->player) {
            return;
        }

        $this->validate(
            ['influence' => 'nullable|integer|min:0|max:99'],
            ['influence.integer' => 'Enter a whole number.', 'influence.min' => 'Influence cannot be negative.', 'influence.max' => 'Influence can be at most 99.']
        );

        $influence = $this->influence === '' || $this->influence === null ? null : (int) $this->influence;
        $this->player->update(['influence' => $influence]);
        $this->influence = $influence ?? '';
        Log::info('Influence updated', ['influence' => $influence]);

        $this->dispatch('flashMessage', [
            $influence === null ? 'Your influence is cleared.' : "Your influence is set to {$influence}.",
            'success'
        ]);
        // Closes the editor and refreshes the player list on this screen, other screens get the broadcast
        $this->dispatch('influence-saved');
        TriggerRefresh::dispatch($this->game);
    }

    public function render()
    {
        return view('livewire.player-influence');
    }
}
