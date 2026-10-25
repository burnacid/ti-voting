<?php

namespace App\Livewire;

use App\Events\TriggerRefresh;
use App\Livewire\Concerns\SharesGameLogContext;
use App\Models\Player;
use App\Services\DraftImporter;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UpdateMiltyUrl extends Component
{
    use SharesGameLogContext;

    public $game;

    // Kept as an id and loaded on every request, as the Speaker can remove the player mid-game
    #[Locked]
    public string $playerId;
    protected ?Player $player = null;
    // A Milty or TI4 Lab draft URL, or a map string
    public $miltyUrl = '';
    public $showModal = false;

    public function mount($game, $player): void
    {
        $this->game = $game;
        $this->playerId = $player->id;
        $this->player = $player;
        $this->miltyUrl = DraftImporter::inputOf($game);
    }

    public function hydrate(): void
    {
        $this->player = Player::find($this->playerId);
    }

    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function updateMiltyUrl(): void
    {
        if (!$this->player?->is_speaker) {
            $this->dispatch('flashMessage', [
                'Only the Speaker can change the map.',
                'error'
            ]);
            return;
        }

        $this->validate([
            'miltyUrl' => 'required|string|max:2000',
        ]);

        // Each import fetches the draft from Milty or TI4 Lab
        $throttleKey = 'draft-import|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 20)) {
            $this->addError('miltyUrl', 'Too many drafts loaded. Try again in ' . ceil(RateLimiter::availableIn($throttleKey) / 60) . ' minutes.');
            return;
        }
        RateLimiter::hit($throttleKey, 600);

        try {
            $draft = (new DraftImporter())->import($this->miltyUrl);
        } catch (InvalidArgumentException $e) {
            $this->addError('miltyUrl', $e->getMessage());
            return;
        }

        $this->game->update($draft);

        Log::info('Map updated', ['source' => $draft['milty_draft_data']['source'] ?? 'milty', 'draft' => $draft['milty_draft_id']]);

        $this->dispatch('flashMessage', [
            "Updated the map successfully.",
            'success'
        ]);

        TriggerRefresh::dispatch($this->game);

        $this->closeModal();
        $this->dispatch('miltyUrlUpdated');
    }

    public function render(): Factory|View|\Illuminate\View\View
    {
        return view('livewire.update-milty-url');
    }
}
