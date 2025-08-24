<?php

namespace App\Livewire;

use App\Services\PlanetService;
use Livewire\Component;
use App\Models\Game;
use App\Models\Player;
use App\Models\Agenda;
use App\Models\Vote;

class GameDashboard extends Component
{
    public Game $game;
    public Player $player;
    public $selectedOption = '';
    public $influenceSpent = 0;

    // Speaker-only properties for creating agendas
    public $newAgendaTitle = '';
    public $newAgendaDescription = '';
    public $agendaType = 'for_against'; // 'for_against', 'elect_player', 'custom'
    public $customOptions = '';
    public $showCreateAgenda = false;
    public $speakerViewResults = false; // Speaker can toggle results view

    public function mount(Game $game, Player $player)
    {
        $this->game = $game;
        $this->player = $player;
    }

    public function rules()
    {
        return [
            'selectedOption' => 'required|string',
            'influenceSpent' => 'required|integer|min:0|max:99',
            'newAgendaTitle' => 'required|string|max:255',
            'newAgendaDescription' => 'required|string|max:1000',
            'agendaType' => 'required|in:for_against,elect_player,elect_planet_any,elect_planet_industrial,elect_planet_cultural,elect_planet_hazardous,elect_planet_non_home,custom',
            'customOptions' => 'required_if:agendaType,custom|string|max:500',
        ];
    }

    public function refreshData()
    {
        // Refresh the game and player data from the database
        $this->game = $this->game->fresh();
        $this->player = $this->player->fresh();

        $this->dispatch('flashMessage', [
            'Data refreshed successfully!',
            'success'
        ]);
    }

    public function toggleCreateAgenda()
    {
        if (!$this->player->is_speaker) {
            $this->dispatch('flashMessage', [
                'Only the Speaker can create agendas.',
                'error'
            ]);
            return;
        }

        $this->showCreateAgenda = !$this->showCreateAgenda;

        if (!$this->showCreateAgenda) {
            $this->reset(['newAgendaTitle', 'newAgendaDescription', 'agendaType', 'customOptions']);
        }
    }

    public function toggleSpeakerResults()
    {
        if (!$this->player->is_speaker) {
            $this->dispatch('flashMessage', [
                'Only the Speaker can view results during voting.',
                'error'
            ]);
            return;
        }

        $this->speakerViewResults = !$this->speakerViewResults;
    }

    public function createAgenda()
    {
        if (!$this->player->is_speaker) {
            $this->dispatch('flashMessage', [
                'Only the Speaker can create agendas.',
                'error'
            ]);
            return;
        }

        // Check if there's already an active agenda
        $currentAgenda = $this->game->currentAgenda();
        if ($currentAgenda) {
            $this->dispatch('flashMessage', [
                'There is already an active agenda. Please end the current voting first.',
                'error'
            ]);
            return;
        }

        $this->validate([
            'newAgendaTitle' => 'required|string|max:255',
            'newAgendaDescription' => 'required|string|max:1000',
            'agendaType' => 'required|in:for_against,elect_player,elect_planet_any,elect_planet_industrial,elect_planet_cultural,elect_planet_hazardous,elect_planet_non_home,custom'
        ]);

        // Determine options based on agenda type
        $options = $this->getAgendaOptions();

        if (empty($options)) {
            $this->dispatch('flashMessage', [
                'Please provide valid options for the agenda.',
                'error'
            ]);
            return;
        }

        Agenda::create([
            'game_id' => $this->game->id,
            'title' => $this->newAgendaTitle,
            'description' => $this->newAgendaDescription,
            'options' => $options,
            'status' => 'voting',
            'voting_started_at' => now(),
        ]);

        $this->reset(['newAgendaTitle', 'newAgendaDescription', 'agendaType', 'customOptions', 'showCreateAgenda']);
        $this->dispatch('flashMessage', [
            'New agenda created and voting has started!',
            'success'
        ]);
        $this->refreshData();
    }

    private function getAgendaOptions(): array
    {
        $options = [];

        switch ($this->agendaType) {
            case 'for_against':
                $options = ['For', 'Against'];
                break;

            case 'elect_player':
                // Get all players in the game as options
                $options = $this->game->players()->pluck('name')->toArray();
                break;

            case 'elect_planet_any':
                // Get all planets in the game as options
                $planets = $this->game->getPlanets()->pluck('name');
                $options = $planets->toArray();
                break;

            case 'elect_planet_industrial':
                // Get all planets in the game as options
                $planets = $this->game->getPlanets('industrial')->pluck('name');
                $options = $planets->toArray();
                break;

            case 'elect_planet_cultural':
                // Get all planets in the game as options
                $planets = $this->game->getPlanets('cultural')->pluck('name');
                $options = $planets->toArray();
                break;

            case 'elect_planet_hazardous':
                // Get all planets in the game as options
                $planets = $this->game->getPlanets('hazardous')->pluck('name');
                $options = $planets->toArray();
                break;

            case 'elect_planet_non_home':
                // Get all planets in the game as options
                $planets = $this->game->getPlanets('non_home')->pluck('name');
                $options = $planets->toArray();
                break;

            case 'custom':
                if (empty($this->customOptions)) {
                    return [];
                }
                // Split custom options by comma and clean them up
                $options = array_map('trim', explode(',', $this->customOptions));
                $options = array_filter($options, function($option) {
                    return !empty($option);
                });
                break;

            default:
                return [];
        }

        // Add Abstain option to all agenda types
        $options[] = 'Abstain';

        return $options;
    }

    public function transferSpeaker($playerId)
    {
        if (!$this->player->is_speaker) {
            $this->dispatch('flashMessage', [
                'Only the Speaker can transfer the Speaker token.',
                'error'
            ]);
            return;
        }

        $newSpeaker = $this->game->players()->find($playerId);
        if (!$newSpeaker) {
            $this->dispatch('flashMessage', [
                'Player not found.',
                'error'
            ]);
            return;
        }

        if ($newSpeaker->id === $this->player->id) {
            $this->dispatch('flashMessage', [
                'You are already the Speaker.',
                'error'
            ]);
            return;
        }

        try {
            $this->game->setSpeaker($newSpeaker);

            $this->dispatch('flashMessage', [
                "Speaker token transferred to {$newSpeaker->name}!",
                'success'
            ]);
            $this->refreshData();
            $this->dispatch('speaker-changed', playerId: $newSpeaker->id);

        } catch (\Exception $e) {
            $this->dispatch('flashMessage', [
                'Failed to transfer speaker token: ' . $e->getMessage(),
                'error'
            ]);
        }
    }

    public function submitVote()
    {
        $currentAgenda = $this->game->currentAgenda();

        if (!$currentAgenda) {
            $this->dispatch('flashMessage', [
                'No active agenda to vote on.',
                'error'
            ]);
            return;
        }

        if ($this->player->hasVotedOn($currentAgenda)) {
            $this->dispatch('flashMessage', [
                'You have already voted on this agenda.',
                'error'
            ]);
            return;
        }

        $this->validate([
            'selectedOption' => 'required|string',
            'influenceSpent' => $this->selectedOption === 'Abstain' ? 'integer|in:0' : 'required|integer|min:0|max:99',
        ]);

        // Force influence to 0 when abstaining
        if ($this->selectedOption === 'Abstain') {
            $this->influenceSpent = 0;
        }

        Vote::create([
            'agenda_id' => $currentAgenda->id,
            'player_id' => $this->player->id,
            'option' => $this->selectedOption,
            'influence_spent' => $this->influenceSpent,
        ]);

        $this->selectedOption = '';
        $this->influenceSpent = 0;

        $this->dispatch('flashMessage', [
            'Your vote has been recorded!',
            'success'
        ]);
        $this->dispatch('vote-submitted');
        $this->refreshData();
    }

    public function endVoting()
    {
        if (!$this->player->is_speaker) {
            $this->dispatch('flashMessage', [
                'Only the Speaker can end voting.',
                'error'
            ]);
            return;
        }

        $currentAgenda = $this->game->currentAgenda();
        if ($currentAgenda) {
            $currentAgenda->update([
                'status' => 'completed',
                'voting_ended_at' => now(),
            ]);

            $this->dispatch('flashMessage', [
                'Voting has ended. Results are now visible to all players.',
                'info'
            ]);
            $this->refreshData();
        }
    }

    public function refreshComponent()
    {
        $this->refreshData();
    }

    public function render()
    {
        $currentAgenda = $this->game->currentAgenda();
        $players = $this->game->players()->get();
        $hasVoted = $currentAgenda ? $this->player->hasVotedOn($currentAgenda) : false;
        $allVoted = $currentAgenda ? $currentAgenda->allPlayersVoted() : false;

        // Show results if:
        // 1. Voting is completed (for everyone)
        // 2. Speaker wants to see results during voting (speaker only)

        $showResults = $currentAgenda && (
            $currentAgenda->status === 'completed' ||
            ($this->player->is_speaker && $this->speakerViewResults)
        );

        $voteResults = $showResults ? $currentAgenda->getVoteResults() : null;

        return view('livewire.game-dashboard', [
            'currentAgenda' => $currentAgenda,
            'players' => $players,
            'hasVoted' => $hasVoted,
            'allVoted' => $allVoted,
            'showResults' => $showResults,
            'voteResults' => $voteResults,
        ]);
    }
}
