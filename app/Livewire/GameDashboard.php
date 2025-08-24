<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Player;
use App\Models\Agenda;
use App\Models\Vote;
use Livewire\Component;
use Livewire\Attributes\On;

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
            'agendaType' => 'required|in:for_against,elect_player,custom',
            'customOptions' => 'required_if:agendaType,custom|string|max:500',
        ];
    }

    public function refreshData()
    {
        // Refresh the game and player data from the database
        $this->game = $this->game->fresh();
        $this->player = $this->player->fresh();

        session()->flash('success', 'Data refreshed successfully!');
    }

    public function toggleCreateAgenda()
    {
        if (!$this->player->is_speaker) {
            session()->flash('error', 'Only the Speaker can create agendas.');
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
            session()->flash('error', 'Only the Speaker can view results during voting.');
            return;
        }

        $this->speakerViewResults = !$this->speakerViewResults;
    }

    public function createAgenda()
    {
        if (!$this->player->is_speaker) {
            session()->flash('error', 'Only the Speaker can create agendas.');
            return;
        }

        // Check if there's already an active agenda
        $currentAgenda = $this->game->currentAgenda();
        if ($currentAgenda) {
            session()->flash('error', 'There is already an active agenda. Please end the current voting first.');
            return;
        }

        $this->validate([
            'newAgendaTitle' => 'required|string|max:255',
            'newAgendaDescription' => 'required|string|max:1000',
            'agendaType' => 'required|in:for_against,elect_player,custom',
        ]);

        // Determine options based on agenda type
        $options = $this->getAgendaOptions();

        if (empty($options)) {
            session()->flash('error', 'Please provide valid options for the agenda.');
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
        session()->flash('success', 'New agenda created and voting has started!');
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
            session()->flash('error', 'Only the Speaker can transfer the Speaker token.');
            return;
        }

        $newSpeaker = $this->game->players()->find($playerId);
        if (!$newSpeaker) {
            session()->flash('error', 'Player not found.');
            return;
        }

        if ($newSpeaker->id === $this->player->id) {
            session()->flash('error', 'You are already the Speaker.');
            return;
        }

        try {
            $this->game->setSpeaker($newSpeaker);

            session()->flash('success', "Speaker token transferred to {$newSpeaker->name}!");
            $this->refreshData();
            $this->dispatch('speaker-changed', playerId: $newSpeaker->id);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to transfer speaker token: ' . $e->getMessage());
        }
    }

    public function submitVote()
    {
        $currentAgenda = $this->game->currentAgenda();

        if (!$currentAgenda) {
            session()->flash('error', 'No active agenda to vote on.');
            return;
        }

        if ($this->player->hasVotedOn($currentAgenda)) {
            session()->flash('error', 'You have already voted on this agenda.');
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

        $this->dispatch('vote-submitted');
        $this->refreshData();
    }

    public function endVoting()
    {
        if (!$this->player->is_speaker) {
            session()->flash('error', 'Only the Speaker can end voting.');
            return;
        }

        $currentAgenda = $this->game->currentAgenda();
        if ($currentAgenda) {
            $currentAgenda->update([
                'status' => 'completed',
                'voting_ended_at' => now(),
            ]);

            session()->flash('success', 'Voting has ended. Results are now visible to all players.');
            $this->refreshData();
        }
    }

    #[On('vote-submitted')]
    #[On('speaker-changed')]
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
