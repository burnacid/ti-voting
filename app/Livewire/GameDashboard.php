<?php

namespace App\Livewire;

use App\Events\TriggerRefresh;
use App\Livewire\Concerns\SharesGameLogContext;
use App\Services\PlanetService;
use App\Support\PlayerGames;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Game;
use App\Models\Player;
use App\Models\Agenda;
use App\Models\Prediction;
use App\Models\Vote;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class GameDashboard extends Component
{
    use SharesGameLogContext;

    // Planet agenda types and the planet type passed to Game::getPlanets()
    private const PLANET_AGENDA_TYPES = [
        'elect_planet_any' => null,
        'elect_planet_industrial' => 'industrial',
        'elect_planet_cultural' => 'cultural',
        'elect_planet_hazardous' => 'hazardous',
        'elect_planet_non_home' => 'non_home',
    ];

    public Game $game;

    // Kept as an id and loaded on every request, as the Speaker can remove the player mid-game
    #[Locked]
    public string $playerId;
    protected ?Player $player = null;
    public $selectedOption = '';
    // Faction picked in the prompt shown when the game has a Milty draft and this player has no drafted faction yet
    public $chosenFaction = '';
    public $influenceSpent = 0;

    // A prediction made aloud after the agenda is revealed, e.g. with a Rider. The Speaker can record one for another player
    public $predictionSource = '';
    public $predictionOption = '';
    public $predictionPlayerId = '';

    // Speaker-only properties for creating agendas
    public $newAgendaTitle = '';
    public $newAgendaDescription = '';
    public $newAgendaCardType = null; // law or directive when created from a preset agenda
    public $newAgendaElect = null; // Elect value of the preset agenda, e.g. planet or law
    public $newAgendaSource = null; // Deck of the preset agenda, e.g. pok or absol
    public $agendaType = 'for_against';
    public $customOptions = '';
    public $customTileNumbers = '';
    public $showCreateAgenda = false;
    public $speakerViewResults = false;
    public $showAffectedPlayers = false; // Speaker shows who an outcome like 'Each player who voted "For"' applies to
    public $selectedPresetAgenda = ''; // New property for preset agenda selection
    public $availableAgendas = []; // New property to store available agendas

    public function mount(Game $game, Player $player)
    {
        $this->game = $game;
        $this->playerId = $player->id;
        $this->player = $player;
        $this->loadAvailableAgendas();

        // Listen for game-specific channel
        $this->js('window.Echo.channel("games.' . $this->game->id . '").listen("TriggerRefresh", () => {
            Livewire.dispatch("triggerRefresh");
        })');
    }

    public function hydrate()
    {
        $this->player = Player::find($this->playerId);

        if (!$this->player) {
            $this->leaveRemovedGame();
        }
    }

    public function exception($e, $stopPropagation)
    {
        // Actions of a removed player fail on the missing player, they are sent away instead
        if (!$this->player) {
            $stopPropagation();
        }
    }

    private function leaveRemovedGame()
    {
        PlayerGames::forget($this->game->code);
        session()->flash('error', 'You were removed from this game.');
        $this->redirectRoute('join-group', $this->game->code);
    }

    private function loadAvailableAgendas()
    {
        $jsonPath = resource_path('data/agendas.json');
        if (file_exists($jsonPath)) {
            $agendas = json_decode(file_get_contents($jsonPath), true);
            // Absol's Lab agendas replace the official agenda deck
            $this->availableAgendas = collect($agendas)
                ->filter(fn ($agenda) => ($agenda['Absol'] ?? false) === (bool) $this->game->absol_agendas)
                ->sortBy('Name')
                ->values()
                ->all();
        }
    }

    public function updatedSelectedPresetAgenda($value)
    {
        if (empty($value)) {
            return;
        }

        $agenda = collect($this->availableAgendas)->firstWhere('Name', $value);

        if (!$agenda) {
            return;
        }

        // Populate form fields based on selected agenda
        $this->newAgendaTitle = $agenda['Name'];
        $this->newAgendaCardType = strtolower($agenda['Type'] ?? '') ?: null;
        $this->newAgendaDescription = $agenda['Effect'];

        // Determine agenda type based on Elect field
        $electType = strtolower($agenda['Elect'] ?? '');
        $this->newAgendaElect = $electType ?: null;
        $this->newAgendaSource = Agenda::sourceOf($agenda);

        $agendaType = array_search($electType, Agenda::ELECT_BY_AGENDA_TYPE, true) ?: 'for_against';

        if (in_array($electType, ['law', 'secret_objective', 'unit_upgrade'])) {
            // These are special cases that might need custom handling
            $this->agendaType = 'custom';
            $this->customOptions = match ($electType) {
                'law' => 'Law 1, Law 2, Law 3',
                'secret_objective' => 'Secret Objective 1, Secret Objective 2',
                // Only the ones a player owns can be elected, the Speaker removes the others
                'unit_upgrade' => 'Carrier II, Cruiser II, Destroyer II, Dreadnought II, Fighter II, Infantry II, PDS II, Space Dock II, War Sun',
            };
        } elseif (array_key_exists($agendaType, self::PLANET_AGENDA_TYPES) && $this->game->milty_draft_data == null) {
            // Planets come from the Milty draft
            $this->agendaType = 'custom';
        } else {
            $this->agendaType = $agendaType;
        }

        $this->dispatch('flashMessage', [
            'Agenda template loaded. You can modify the details before creating.',
            'success'
        ]);
    }

    public function clearPresetSelection()
    {
        $this->selectedPresetAgenda = '';
        $this->reset(['newAgendaTitle', 'newAgendaDescription', 'newAgendaCardType', 'newAgendaElect', 'newAgendaSource', 'agendaType', 'customOptions', 'customTileNumbers']);
    }

    public function rules()
    {
        return [
            'selectedOption' => 'required|string',
            'influenceSpent' => 'required|integer|min:0|max:99',
            'newAgendaTitle' => 'required|string|max:255',
            'newAgendaDescription' => 'required|string|max:1000',
            'newAgendaCardType' => 'required|in:law,directive',
            'agendaType' => 'required|in:for_against,elect_player,elect_planet_any,elect_planet_industrial,elect_planet_cultural,elect_planet_hazardous,elect_planet_non_home,custom',
            'customOptions' => 'required_if:agendaType,custom|string|max:500',
            'customTileNumbers' => 'nullable|string|max:255',
        ];
    }

    #[On('triggerRefresh')]
    #[On('miltyUrlUpdated')]
    #[On('influence-saved')]
    public function listenForRefresh(){
        $this->refreshData();
    }

    public function refreshData()
    {
        // Refresh the game and player data from the database
        $this->game = $this->game->fresh();
        $this->player = Player::find($this->playerId);

        if (!$this->player) {
            $this->leaveRemovedGame();
        }

//        $this->dispatch('flashMessage', [
//            'Data refreshed successfully!',
//            'success'
//        ]);
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
            $this->reset(['newAgendaTitle', 'newAgendaDescription', 'newAgendaCardType', 'newAgendaElect', 'newAgendaSource', 'agendaType', 'customOptions', 'customTileNumbers']);
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
            'newAgendaCardType' => 'required|in:law,directive',
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

        $agenda = Agenda::create([
            'game_id' => $this->game->id,
            'title' => $this->newAgendaTitle,
            'description' => $this->newAgendaDescription,
            'type' => $this->newAgendaCardType,
            'elect' => $this->resolveAgendaElect(),
            'source' => $this->newAgendaSource,
            'options' => $options,
            'status' => 'voting',
            'reveal_step' => Agenda::STEP_WHEN_REVEALED,
            'voting_started_at' => now(),
        ]);

        Log::info('Agenda revealed', ['agenda' => $agenda->title, 'options' => count($options)]);

        $this->reset(['newAgendaTitle', 'newAgendaDescription', 'newAgendaCardType', 'newAgendaElect', 'newAgendaSource', 'agendaType', 'customOptions', 'showCreateAgenda', 'showAffectedPlayers']);
        $this->dispatch('flashMessage', [
            'New agenda revealed. Ask the players for "When an agenda is revealed" action cards.',
            'success'
        ]);

        TriggerRefresh::dispatch($this->game);

        $this->refreshData();
    }

    private function resolveAgendaElect(): ?string
    {
        // A preset's own Elect value wins, as some (law, strategy card) use custom options
        if (array_key_exists($this->newAgendaElect ?? '', Agenda::ELECT_LABELS)) {
            return $this->newAgendaElect;
        }

        return Agenda::ELECT_BY_AGENDA_TYPE[$this->agendaType] ?? null;
    }

    private function getAgendaOptions(): array
    {
        $options = [];
        $planetService = app(PlanetService::class);
        $customTileNumbers = [];

        // Parse custom tile numbers if provided
        if (!empty($this->customTileNumbers)) {
            $customTileNumbers = array_map('trim', explode(',', $this->customTileNumbers));
            $customTileNumbers = array_filter($customTileNumbers, function($num) {
                return is_numeric($num);
            });
            $customTileNumbers = array_map('intval', $customTileNumbers);
        }

        if (array_key_exists($this->agendaType, self::PLANET_AGENDA_TYPES)) {
            $planets = $this->game->getPlanets(self::PLANET_AGENDA_TYPES[$this->agendaType]);

            // Add planets from custom tile numbers
            if (!empty($customTileNumbers)) {
                foreach ($planetService->getPlanetsByTileNumber($customTileNumbers) as $name => $planet) {
                    $planets[$name] = $planet;
                }
            }

            return [...$planets->pluck('name')->sort()->values(), 'Abstain'];
        }

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
            Log::info('Speaker token transferred', ['to' => $newSpeaker->name]);

            $this->dispatch('flashMessage', [
                "Speaker token transferred to {$newSpeaker->name}!",
                'success'
            ]);
            $this->refreshData();
            TriggerRefresh::dispatch($this->game);

        } catch (\Exception $e) {
            report($e);
            $this->dispatch('flashMessage', [
                'Failed to transfer speaker token: ' . $e->getMessage(),
                'error'
            ]);
        }
    }

    public function chooseFaction()
    {
        $factions = $this->game->availableFactions($this->player);

        $this->validate(
            ['chosenFaction' => ['required', Rule::in($factions->keys())]],
            ['chosenFaction.required' => 'Choose the faction you drafted.', 'chosenFaction.in' => 'Another player already chose this faction.']
        );

        $this->player->update(['faction' => $this->chosenFaction]);
        Log::info('Faction chosen', ['faction' => $this->chosenFaction]);

        $this->reset('chosenFaction');
        TriggerRefresh::dispatch($this->game);
    }

    // Another player confirms that the person asking to rejoin as this player really is them
    public function approveRejoin($playerId)
    {
        $requested = $this->game->players()->find($playerId);
        if (!$requested || $requested->id === $this->player->id || !$requested->hasPendingRejoinRequest()) {
            return;
        }

        $requested->update(['rejoin_approved_at' => now()]);
        Log::info('Rejoin approved', ['rejoined' => $requested->name]);

        $this->dispatch('flashMessage', ["{$requested->name} is let back in.", 'success']);
        TriggerRefresh::dispatch($this->game);
    }

    public function declineRejoin($playerId)
    {
        $requested = $this->game->players()->find($playerId);
        if (!$requested || $requested->id === $this->player->id || !$requested->hasPendingRejoinRequest()) {
            return;
        }

        $requested->clearRejoinRequest();
        Log::info('Rejoin declined', ['rejoined' => $requested->name]);

        TriggerRefresh::dispatch($this->game);
    }

    public function removePlayer($playerId)
    {
        if (!$this->player->is_speaker) {
            $this->dispatch('flashMessage', [
                'Only the Speaker can remove players.',
                'error'
            ]);
            return;
        }

        $removedPlayer = $this->game->players()->find($playerId);
        if (!$removedPlayer || $removedPlayer->id === $this->player->id) {
            return;
        }

        $this->game->removePlayer($removedPlayer);
        Log::info('Player removed', ['removed' => $removedPlayer->name]);

        $this->dispatch('flashMessage', [
            "{$removedPlayer->name} was removed from the game.",
            'info'
        ]);
        TriggerRefresh::dispatch($this->game);
        $this->refreshData();
    }

    public function toggleVotingBlock($playerId)
    {
        if (!$this->player->is_speaker) {
            $this->dispatch('flashMessage', [
                'Only the Speaker can decide who may vote.',
                'error'
            ]);
            return;
        }

        // Only while checking "When" and "After" action cards, before anyone can vote
        $currentAgenda = $this->game->currentAgenda();
        if (!$currentAgenda || $currentAgenda->reveal_step === null) {
            return;
        }

        $blockedPlayer = $this->game->players()->find($playerId);
        if (!$blockedPlayer) {
            return;
        }

        $blockedIds = collect($currentAgenda->blocked_player_ids ?? []);

        if ($blockedIds->contains($blockedPlayer->id)) {
            $blockedIds = $blockedIds->reject(fn ($id) => $id === $blockedPlayer->id);
            $message = "{$blockedPlayer->name} can vote on this agenda again.";
            Log::info('Player allowed to vote again', ['target' => $blockedPlayer->name, 'agenda' => $currentAgenda->title]);
        } else {
            $blockedIds->push($blockedPlayer->id);
            $message = "{$blockedPlayer->name} cannot vote on this agenda.";
            Log::info('Player blocked from voting', ['target' => $blockedPlayer->name, 'agenda' => $currentAgenda->title]);
        }

        $currentAgenda->update(['blocked_player_ids' => $blockedIds->values()->all()]);

        $this->dispatch('flashMessage', [$message, 'info']);
        TriggerRefresh::dispatch($this->game);
        $this->refreshData();
    }

    public function makePrediction()
    {
        // Only while checking "When" and "After" action cards, before anyone can vote
        $currentAgenda = $this->game->currentAgenda();
        if (!$currentAgenda || $currentAgenda->reveal_step === null) {
            $this->dispatch('flashMessage', [
                'Predictions can only be made before voting opens.',
                'error'
            ]);
            return;
        }

        $this->validate([
            'predictionSource' => ['required', Rule::in(array_keys(Prediction::SOURCES))],
            'predictionOption' => ['required', 'string', Rule::in(array_diff($currentAgenda->options, ['Abstain']))],
        ], [
            'predictionSource.required' => 'Choose the card or ability you predict with.',
            'predictionOption.required' => 'Choose the outcome you predict.',
        ]);

        // The Speaker can record a prediction for another player, everyone else only for themselves
        $predictor = $this->player->is_speaker && $this->predictionPlayerId
            ? $this->game->players()->find($this->predictionPlayerId)
            : $this->player;

        if (!$predictor) {
            return;
        }

        try {
            Prediction::create([
                'agenda_id' => $currentAgenda->id,
                'player_id' => $predictor->id,
                'source' => $this->predictionSource,
                'option' => $this->predictionOption,
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->dispatch('flashMessage', [
                "{$predictor->name} already predicted with this card on this agenda.",
                'error'
            ]);
            return;
        }

        $sourceLabel = Prediction::SOURCES[$this->predictionSource]['label'];
        Log::info('Prediction made', ['agenda' => $currentAgenda->title, 'player' => $predictor->name, 'source' => $sourceLabel, 'option' => $this->predictionOption]);

        $this->dispatch('flashMessage', [
            "{$predictor->name} predicts {$this->predictionOption} with {$sourceLabel}, and cannot vote on this agenda.",
            'success'
        ]);
        $this->reset(['predictionSource', 'predictionOption', 'predictionPlayerId']);

        TriggerRefresh::dispatch($this->game);
        $this->refreshData();
    }

    // A prediction recorded by mistake can be removed by its player or the Speaker, until voting opens
    public function removePrediction($predictionId)
    {
        $currentAgenda = $this->game->currentAgenda();
        if (!$currentAgenda || $currentAgenda->reveal_step === null) {
            return;
        }

        $prediction = $currentAgenda->predictions()->with('player')->find($predictionId);
        if (!$prediction || (!$this->player->is_speaker && $prediction->player_id !== $this->player->id)) {
            return;
        }

        $prediction->delete();
        Log::info('Prediction removed', ['agenda' => $currentAgenda->title, 'player' => $prediction->player->name, 'source' => $prediction->source_label]);

        TriggerRefresh::dispatch($this->game);
        $this->refreshData();
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

        if (!$currentAgenda->isVotingOpen()) {
            $this->dispatch('flashMessage', [
                'Voting has not started yet. The Speaker is still checking for action cards.',
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

        if ($currentAgenda->isPlayerBlocked($this->player)) {
            $this->dispatch('flashMessage', [
                'You cannot vote on this agenda.',
                'error'
            ]);
            return;
        }

        $oneVoteEach = $this->game->representative_government;

        $this->validate([
            'selectedOption' => ['required', 'string', Rule::in($currentAgenda->options)],
            'influenceSpent' => $this->selectedOption === 'Abstain' || $oneVoteEach ? 'nullable' : 'required|integer|min:1|max:99',
        ]);

        // Force influence to 0 when abstaining, and to 1 vote under Representative Government
        if ($this->selectedOption === 'Abstain') {
            $this->influenceSpent = 0;
        } elseif ($oneVoteEach) {
            $this->influenceSpent = 1;
        }

        try {
            Vote::create([
                'agenda_id' => $currentAgenda->id,
                'player_id' => $this->player->id,
                'option' => $this->selectedOption,
                'influence_spent' => $this->influenceSpent,
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->dispatch('flashMessage', [
                'You have already voted on this agenda.',
                'error'
            ]);
            return;
        }

        Log::info('Vote cast', ['agenda' => $currentAgenda->title, 'option' => $this->selectedOption, 'influence' => (int) $this->influenceSpent]);

        $this->selectedOption = '';
        $this->influenceSpent = 0;

        $this->dispatch('flashMessage', [
            'Your vote has been recorded!',
            'success'
        ]);

        // The last vote ends voting, so the Speaker does not have to
        if ($currentAgenda->allPlayersVoted()) {
            $this->completeAgenda($currentAgenda);
        }

        TriggerRefresh::dispatch($this->game);

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
        if ($currentAgenda && !$currentAgenda->isVotingOpen()) {
            $this->dispatch('flashMessage', [
                'Voting has not started yet. Finish checking for action cards first.',
                'error'
            ]);
            return;
        }

        if ($currentAgenda) {
            $this->completeAgenda($currentAgenda);
            $this->dispatch('flashMessage', [
                'Voting has ended. Please announce the results to the other players.',
                'info'
            ]);
            $this->refreshData();
            TriggerRefresh::dispatch($this->game);
        }
    }

    // Ends voting, by the Speaker or by itself once every player who may vote has voted
    private function completeAgenda(Agenda $agenda): void
    {
        $agenda->update([
            'status' => 'completed',
            'voting_ended_at' => now(),
        ]);
        Log::info('Voting ended', ['agenda' => $agenda->title, 'votes' => $agenda->votes()->count()]);

        $this->applyRepresentativeGovernment($agenda);
    }

    /**
     * Put Representative Government in effect when it was voted "For". On a tie it waits for the Speaker to break it.
     */
    private function applyRepresentativeGovernment(Agenda $agenda): void
    {
        if (strcasecmp($agenda->title, Game::REPRESENTATIVE_GOVERNMENT) !== 0 || $this->game->representative_government) {
            return;
        }

        $outcome = $agenda->outcome();

        if ($outcome === 'For') {
            $this->game->update(['representative_government' => true]);
            Log::info('Representative Government put in effect', ['tiebreak' => $agenda->tiebreak_option !== null]);
            $this->dispatch('flashMessage', [
                'Representative Government passed: from now on each player casts 1 vote on each agenda.',
                'success'
            ]);
        } elseif ($outcome === null && $this->player->is_speaker) {
            $this->dispatch('flashMessage', [
                'Representative Government is tied. As Speaker you decide: break the tie in the results.',
                'info'
            ]);
        }
    }

    // On a tie the Speaker decides the outcome of the agenda that just ended
    public function breakTie($option)
    {
        if (!$this->player->is_speaker || $this->game->currentAgenda()) {
            return;
        }

        $agenda = $this->game->lastCompletedAgenda();
        if (!$agenda || !$agenda->isTied() || !in_array($option, $agenda->leadingOptions(), true)) {
            return;
        }

        $agenda->update(['tiebreak_option' => $option]);
        Log::info('Tie broken by the Speaker', ['agenda' => $agenda->title, 'outcome' => $option]);

        $this->dispatch('flashMessage', ["You broke the tie: {$option} wins.", 'success']);
        $this->applyRepresentativeGovernment($agenda);
        TriggerRefresh::dispatch($this->game);
        $this->refreshData();
    }

    public function toggleAffectedPlayers()
    {
        if (!$this->player->is_speaker) {
            return;
        }

        $this->showAffectedPlayers = !$this->showAffectedPlayers;
    }

    public function toggleRepresentativeGovernment()
    {
        if (!$this->player->is_speaker) {
            return;
        }

        $this->game->update(['representative_government' => !$this->game->representative_government]);
        Log::info($this->game->representative_government ? 'Representative Government put in effect' : 'Representative Government removed');

        $this->dispatch('flashMessage', [
            $this->game->representative_government
                ? 'Representative Government is in effect: each player casts 1 vote on each agenda.'
                : 'Representative Government is no longer in effect: players spend influence to vote again.',
            'info'
        ]);
        TriggerRefresh::dispatch($this->game);
        $this->refreshData();
    }

    // Star Chart: draw a random system tile that is not on the map yet. Any player can draw, everyone sees the result
    public function drawStarChart()
    {
        if (!$this->game->milty_draft_data) {
            return;
        }

        $tile = $this->game->drawStarChartTile($this->player);

        if (!$tile) {
            $this->dispatch('flashMessage', ['There are no system tiles left to draw.', 'error']);
            return;
        }

        Log::info('Star Chart tile drawn', ['tile' => $tile]);
        $this->dispatch('flashMessage', ["Star Chart drew system tile {$tile}.", 'success']);
        TriggerRefresh::dispatch($this->game);
        $this->refreshData();
    }

    // The Speaker can undo a draw made by mistake, which puts the tile back in the pool
    public function removeStarChartTile($tile)
    {
        if (!$this->player->is_speaker || !$this->game->starChartTileNumbers()->contains((string) $tile)) {
            return;
        }

        $this->game->removeStarChartTile((string) $tile);
        Log::info('Star Chart tile removed', ['tile' => (string) $tile]);
        TriggerRefresh::dispatch($this->game);
        $this->refreshData();
    }

    public function toggleAbsolAgendas()
    {
        if (!$this->player->is_speaker) {
            return;
        }

        $this->game->update(['absol_agendas' => !$this->game->absol_agendas]);
        Log::info($this->game->absol_agendas ? "Absol's agendas turned on" : "Absol's agendas turned off");

        $this->clearPresetSelection();
        $this->loadAvailableAgendas();

        $this->dispatch('flashMessage', [
            $this->game->absol_agendas
                ? "The agenda list now shows the agendas of Absol's Lab."
                : 'The agenda list now shows the official agendas.',
            'info',
        ]);
        TriggerRefresh::dispatch($this->game);
        $this->refreshData();
    }

    public function toggleFracture()
    {
        if (!$this->player->is_speaker || !$this->game->isThundersEdgeGame()) {
            return;
        }

        $this->game->update(['fracture_in_play' => !$this->game->fracture_in_play]);

        $keptPlanets = $this->syncFracturePlanetsOnCurrentAgenda();
        Log::info($this->game->fracture_in_play ? 'Fracture put in play' : 'Fracture taken out of play');

        if ($this->game->fracture_in_play) {
            $message = 'The Fracture is in play. Its planets are added to planet agendas, including the current one.';
        } else {
            $message = 'The Fracture is no longer in play. Its planets are removed from planet agendas, including the current one.';

            if ($keptPlanets->isNotEmpty()) {
                $message .= ' ' . $keptPlanets->implode(', ') . ' stayed on the current agenda because players already voted for them.';
            }
        }

        $this->dispatch('flashMessage', [$message, 'info']);
        TriggerRefresh::dispatch($this->game);
        $this->refreshData();
    }

    /**
     * Add or remove the Fracture planets on the open agenda to match the Fracture toggle.
     * Returns the Fracture planets that were kept because players already voted for them.
     */
    private function syncFracturePlanetsOnCurrentAgenda(): Collection
    {
        // The Fracture planets have no trait, so only these agendas can elect them
        $currentAgenda = $this->game->currentAgenda();
        if (!$currentAgenda || !in_array($currentAgenda->elect, ['planet', 'non_home_non_mecathol'])) {
            return collect();
        }

        $fracturePlanets = collect(app(PlanetService::class)->getPlanetsByTileNumber(PlanetService::FRACTURE_TILES))->pluck('name');
        $planets = collect($currentAgenda->options)->reject(fn ($option) => $option === 'Abstain');
        $keptPlanets = collect();

        if ($this->game->fracture_in_play) {
            $planets = $planets->merge($fracturePlanets)->unique();
        } else {
            // Removing an option that has votes would drop those votes from the results
            $keptPlanets = $currentAgenda->votes()->whereIn('option', $fracturePlanets)->distinct()->pluck('option');
            $planets = $planets->reject(fn ($planet) => $fracturePlanets->contains($planet) && !$keptPlanets->contains($planet));
        }

        $currentAgenda->update(['options' => $planets->sort()->values()->push('Abstain')->all()]);

        return $keptPlanets->sort()->values();
    }

    public function completeRevealStep()
    {
        if (!$this->player->is_speaker) {
            $this->dispatch('flashMessage', [
                'Only the Speaker can continue the agenda.',
                'error'
            ]);
            return;
        }

        $currentAgenda = $this->game->currentAgenda();
        if (!$currentAgenda || $currentAgenda->reveal_step === null) {
            return;
        }

        Log::info('Reveal step done', ['agenda' => $currentAgenda->title, 'step' => $currentAgenda->reveal_step_label]);
        $nextStep = $currentAgenda->nextRevealStep();

        if ($nextStep) {
            $currentAgenda->update(['reveal_step' => $nextStep]);
            $message = $nextStep === Agenda::STEP_ARGENT_VOTES
                ? 'The Argent Flight votes first. Ask them to announce their votes to everyone.'
                : 'Ask the players for "After an agenda is revealed" action cards.';
        } else {
            $currentAgenda->update(['reveal_step' => null, 'voting_started_at' => now()]);
            $message = 'Voting has started!';
            Log::info('Voting opened', ['agenda' => $currentAgenda->title]);
        }

        $this->dispatch('flashMessage', [$message, 'success']);
        TriggerRefresh::dispatch($this->game);
        $this->refreshData();
    }

    public function discardAgenda()
    {
        if (!$this->player->is_speaker) {
            $this->dispatch('flashMessage', [
                'Only the Speaker can discard the agenda.',
                'error'
            ]);
            return;
        }

        // Only before voting opens, e.g. when the agenda is vetoed
        $currentAgenda = $this->game->currentAgenda();
        if (!$currentAgenda || $currentAgenda->reveal_step === null) {
            return;
        }

        $currentAgenda->delete();
        Log::info('Agenda discarded', ['agenda' => $currentAgenda->title]);

        $this->dispatch('flashMessage', [
            'Agenda discarded. Reveal the next agenda.',
            'info'
        ]);
        TriggerRefresh::dispatch($this->game);
        $this->refreshData();
    }

    /**
     * This player's correct predictions on the agenda that just ended whose reward depends on who voted for the outcome,
     * e.g. The Nekro Virus taking a technology. Shown until the next agenda is revealed.
     */
    private function correctVoterPredictions(): Collection
    {
        if ($this->game->currentAgenda()) {
            return collect();
        }

        $agenda = $this->game->lastCompletedAgenda();

        return $agenda
            ? $agenda->predictions()->with('agenda')->where('player_id', $this->player->id)->get()
                ->filter(fn ($prediction) => $prediction->showsVoters() && $prediction->isCorrect())
                ->map(fn ($prediction) => [
                    'prediction' => $prediction,
                    'voters' => $agenda->playersWhoVoted($prediction->option),
                ])
                ->values()
            : collect();
    }

    public function refreshComponent()
    {
        $this->refreshData();
    }

    public function render()
    {
        if (!$this->player) {
            return '<div></div>';
        }

        $currentAgenda = $this->game->currentAgenda();

        // Results of the last ended agenda remain visible to the Speaker only
        if (!$currentAgenda && $this->player->is_speaker) {
            $currentAgenda = $this->game->lastCompletedAgenda();
        }

        $players = $this->game->players()->get();
        $hasVoted = $currentAgenda ? $this->player->hasVotedOn($currentAgenda) : false;
        $allVoted = $currentAgenda ? $currentAgenda->allPlayersVoted() : false;

        if($currentAgenda) {
            if ($allVoted && $this->player->is_speaker) {
                $this->speakerViewResults = true;
            }else{
                $this->speakerViewResults = false;
            }
        }

        // Show results if:
        // 1. Voting is completed (for everyone)
        // 2. Speaker wants to see results during voting (speaker only)

        $showResults = $currentAgenda && (
            $currentAgenda->status === 'completed' ||
            ($this->player->is_speaker && $this->speakerViewResults)
        );

        $voteResults = $showResults ? $currentAgenda->getVoteResults() : null;

        return view('livewire.game-dashboard', [
            'player' => $this->player,
            // Asked when the game has a Milty draft and this player's faction is not one of the drafted factions
            'factionChoices' => Game::factionsFromDraft($this->game->milty_draft_data)->has($this->player->faction ?? '')
                ? collect()
                : $this->game->availableFactions($this->player),
            'starChartDraws' => collect($this->game->star_chart_tiles ?? [])->reverse()->map(fn ($draw) => [
                ...$draw,
                'planets' => app(PlanetService::class)->getTile($draw['tile'])['planets'] ?? [],
            ])->values(),
            'starChartPoolSize' => $this->game->milty_draft_data ? $this->game->starChartPool()->count() : 0,
            'rejoinRequests' => $players->filter(fn ($gamePlayer) => $gamePlayer->id !== $this->player->id && $gamePlayer->hasPendingRejoinRequest()),
            'currentAgenda' => $currentAgenda,
            'players' => $players,
            'hasVoted' => $hasVoted,
            'allVoted' => $allVoted,
            'showResults' => $showResults,
            'voteResults' => $voteResults,
            'predictions' => $currentAgenda ? $currentAgenda->predictions()->with('player')->oldest()->get() : collect(),
            'correctVoterPredictions' => $this->correctVoterPredictions(),
        ]);
    }
}
