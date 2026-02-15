<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Player;
use App\Services\MiltyService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CreateGame extends Component
{
    public $gameName = '';
    public $playerName = '';
    public $faction = '';

    public $miltyUrl = '';
    public $hasMiltyData = false;
    public $miltyDraftData = null;
    public $miltyDraftId = null;
    public $miltyError = null;

    public function rules()
    {
        return [
            'gameName' => 'required|string|max:255',
            'playerName' => 'required|string|max:255',
            'faction' => 'nullable|string|max:255',
            'miltyUrl' => 'nullable|url',
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
        $miltyService = new MiltyService();
        $draftId = $miltyService->extractDraftId($this->miltyUrl);

        if (!$draftId) {
            $this->miltyError = 'Invalid Milty URL format. Expected format: https://milty.shenanigans.be/d/{draft_id}';
            $this->resetMiltyData();
            return;
        }

        $draftData = $miltyService->fetchDraftData($draftId);

        if (!$draftData) {
            $this->miltyError = 'Could not fetch Milty draft data. Please check the URL and try again.';
            $this->resetMiltyData();
            return;
        }

        $this->miltyDraftId = $draftId;
        $this->miltyDraftData = $draftData;
        $this->hasMiltyData = true;
        $this->miltyError = null;
    }

    protected function resetMiltyData()
    {
        $this->miltyDraftId = null;
        $this->miltyDraftData = null;
        $this->hasMiltyData = false;
    }

    public function createGame()
    {
        $this->validate();

        $this->fetchMiltyData();

        $game = Game::create([
            'code' => Game::generateCode(),
            'name' => $this->gameName,
            'status' => 'waiting',
            'milty_url' => $this->miltyUrl,
            'milty_draft_id' => $this->miltyDraftId,
            'milty_draft_data' => $this->miltyDraftData,
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
