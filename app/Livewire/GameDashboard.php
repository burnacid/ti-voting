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
    public $showResults = false;

    // Speaker-only properties
    public $newAgendaTitle = '';
    public $newAgendaDescription = '';
    public $agendaOptions = ['For', 'Against'];
    public $customOptions = '';

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
        ];
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
            'influenceSpent' => 'required|integer|min:0|max:99',
        ]);

        Vote::create([
            'agenda_id' => $currentAgenda->id,
            'player_id' => $this->player->id,
            'option' => $this->selectedOption,
            'influence_spent' => $this->influenceSpent,
        ]);

        $this->selectedOption = '';
        $this->influenceSpent = 0;

        session()->flash('success', 'Your vote has been submitted!');

        // Refresh the component
        $this->dispatch('vote-submitted');
    }

    public function createAgenda()
    {
        if (!$this->player->is_speaker) {
            session()->flash('error', 'Only the Speaker can create agendas.');
            return;
        }

        $this->validate([
            'newAgendaTitle' => 'required|string|max:255',
            'newAgendaDescription' => 'required|string|max:1000',
        ]);

        // Parse custom options if provided
        $options = $this->agendaOptions;
        if (!empty($this->customOptions)) {
            $customOptionsList = array_map('trim', explode(',', $this->customOptions));
            $options = array_filter($customOptionsList);
        }

        // End any current voting
        $this->game->agendas()->where('status', 'voting')->update(['status' => 'completed']);

        Agenda::create([
            'game_id' => $this->game->id,
            'title' => $this->newAgendaTitle,
            'description' => $this->newAgendaDescription,
            'options' => $options,
            'status' => 'voting',
            'voting_started_at' => now(),
        ]);

        $this->reset(['newAgendaTitle', 'newAgendaDescription', 'customOptions']);
        session()->flash('success', 'New agenda created and voting has started!');
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

            $this->showResults = true;
            session()->flash('success', 'Voting has ended. Results are now visible.');
        }
    }

    public function toggleResults()
    {
        if (!$this->player->is_speaker) {
            return;
        }

        $this->showResults = !$this->showResults;
    }

    public function transferSpeaker($playerId)
    {
        if (!$this->player->is_speaker) {
            session()->flash('error', 'Only the Speaker can transfer the Speaker token.');
            return;
        }

        $newSpeaker = $this->game->players()->find($playerId);
        if ($newSpeaker) {
            $this->game->setSpeaker($newSpeaker);
            session()->flash('success', "Speaker token transferred to {$newSpeaker->name}!");

            // Refresh the page since speaker status changed
            return redirect()->route('game.show', $this->game->code);
        }
    }

    #[On('vote-submitted')]
    public function refreshComponent()
    {
        $this->game = $this->game->fresh();
    }

    public function render()
    {
        $currentAgenda = $this->game->currentAgenda();
        $players = $this->game->players()->get();
        $hasVoted = $currentAgenda ? $this->player->hasVotedOn($currentAgenda) : false;
        $allVoted = $currentAgenda ? $currentAgenda->allPlayersVoted() : false;
        $voteResults = ($currentAgenda && ($this->showResults || $this->player->is_speaker))
            ? $currentAgenda->getVoteResults()
            : null;

        return view('livewire.game-dashboard', [
            'currentAgenda' => $currentAgenda,
            'players' => $players,
            'hasVoted' => $hasVoted,
            'allVoted' => $allVoted,
            'voteResults' => $voteResults,
        ]);
    }
}
