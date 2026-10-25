<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Player;
use App\Services\DraftImporter;
use App\Support\PlayerGames;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CreateGame extends Component
{
    public $gameName = '';
    public $playerName = '';
    public $faction = '';

    // A Milty or TI4 Lab draft URL, or a map string
    public $miltyUrl = '';
    // Set only by fetchMiltyData, locked so a client cannot save its own draft URL or data
    #[Locked]
    public $miltyDraftUrl = null;
    #[Locked]
    public $hasMiltyData = false;
    #[Locked]
    public $miltyDraftData = null;
    #[Locked]
    public $miltyDraftId = null;
    public $miltyError = null;

    public function rules()
    {
        return [
            'gameName' => 'required|string|max:255',
            'playerName' => 'required|string|max:255',
            'miltyUrl' => 'nullable|string|max:2000',
        ];
    }

    public function updatedMiltyUrl()
    {
        if (empty($this->miltyUrl)) {
            $this->resetMiltyData();
            return;
        }

        $this->validateOnly('miltyUrl');
        $this->fetchMiltyData();
    }

    protected function fetchMiltyData()
    {
        // Each import fetches the draft from Milty or TI4 Lab
        $throttleKey = 'draft-import|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 20)) {
            $this->miltyError = 'Too many drafts loaded. Try again in ' . ceil(RateLimiter::availableIn($throttleKey) / 60) . ' minutes.';
            $this->resetMiltyData();
            return;
        }
        RateLimiter::hit($throttleKey, 600);

        try {
            $draft = (new DraftImporter())->import($this->miltyUrl);
        } catch (InvalidArgumentException $e) {
            $this->miltyError = $e->getMessage();
            $this->resetMiltyData();
            return;
        }

        $this->miltyDraftUrl = $draft['milty_url'];
        $this->miltyDraftId = $draft['milty_draft_id'];
        $this->miltyDraftData = $draft['milty_draft_data'];
        $this->hasMiltyData = true;
        $this->miltyError = null;

        // A faction picked for an earlier draft URL may not be in this draft
        if (!$this->factions()->has($this->faction)) {
            $this->faction = '';
        }
    }

    /**
     * The factions picked in the Milty draft, as faction => name of the player in the draft.
     */
    protected function factions()
    {
        return Game::factionsFromDraft($this->miltyDraftData);
    }

    protected function resetMiltyData()
    {
        $this->miltyDraftUrl = null;
        $this->miltyDraftId = null;
        $this->miltyDraftData = null;
        $this->hasMiltyData = false;
        $this->faction = '';
    }

    public function createGame()
    {
        $this->validate();

        if (!empty($this->miltyUrl) && !$this->hasMiltyData) {
            $this->fetchMiltyData();

            if (!$this->hasMiltyData) {
                $this->addError('miltyUrl', $this->miltyError);
                return;
            }
        }

        // With a Milty draft, players choose the faction they drafted
        if ($this->factions()->isNotEmpty()) {
            $this->validate(
                ['faction' => ['required', Rule::in($this->factions()->keys())]],
                ['faction.required' => 'Choose the faction you drafted.']
            );
        }

        $throttleKey = 'create-game|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $this->addError('gameName', 'Too many games created. Try again in ' . ceil(RateLimiter::availableIn($throttleKey) / 60) . ' minutes.');
            return;
        }
        RateLimiter::hit($throttleKey, 3600);

        $game = Game::create([
            'code' => Game::generateCode(),
            'name' => $this->gameName,
            'status' => 'waiting',
            'milty_url' => $this->miltyDraftUrl,
            'milty_draft_id' => $this->miltyDraftId,
            'milty_draft_data' => $this->miltyDraftData,
        ]);

        $player = Player::create([
            'game_id' => $game->id,
            'name' => $this->playerName,
            'session_token' => Player::generateSessionToken(),
            'faction' => $this->factions()->has($this->faction) ? $this->faction : null,
            'is_speaker' => true, // First player is the speaker
        ]);

        // Update game with speaker
        $game->update(['speaker_id' => $player->id]);

        Log::info('Game created', [
            'game' => $game->code,
            'player' => $player->name,
            'name' => $game->name,
            'milty' => $game->milty_draft_data['source'] ?? ($game->milty_draft_data ? 'milty' : null),
        ]);

        // Remember the game in this browser
        PlayerGames::remember($player);

        return redirect()->route('game.show', $game->code);
    }

    public function render()
    {
        return view('livewire.create-game', ['factions' => $this->factions()]);
    }
}
