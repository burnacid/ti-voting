<?php

namespace App\Livewire\Admin;

use App\Models\Agenda;
use App\Models\Game;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Games extends Component
{
    use WithPagination;

    // Filters with the values they accept, as value => label. Other values, e.g. from an edited URL, are ignored
    public const MAP_FILTERS = ['none' => 'No map'] + Game::MAP_SOURCE_LABELS;

    public const OPTION_FILTERS = [
        'voting' => 'Voting now',
        'absol' => "Absol's agendas",
        'managed_influence' => 'Managed influence',
        'open_votes' => 'Hidden Agenda off',
        'show_results' => 'Results shown to all',
        'representative_government' => 'Representative Government',
        'fracture' => 'Fracture in play',
    ];

    public const SORTS = [
        'newest' => 'Newest first',
        'activity' => 'Last agenda',
        'players' => 'Most players',
    ];

    #[Url(except: '')]
    public $search = '';

    #[Url(except: '')]
    public $map = '';

    #[Url(except: '')]
    public $expansion = '';

    #[Url(except: '')]
    public $option = '';

    #[Url(except: 'newest')]
    public $sort = 'newest';

    public $cleanupDays;
    public $cleanupOutput = null;

    public function mount()
    {
        $this->cleanupDays = Game::retentionDays();
    }

    public function updated($property)
    {
        if (in_array($property, ['search', 'map', 'expansion', 'option', 'sort'])) {
            $this->resetPage();
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'map', 'expansion', 'option', 'sort']);
        $this->resetPage();
    }

    public function deleteGame($gameId)
    {
        $game = Game::find($gameId);
        if (!$game) {
            return;
        }

        // Players, agendas and votes are deleted with the game
        $game->delete();
        Log::info('Admin deleted a game', ['game' => $game->code, 'name' => $game->name, 'admin' => Auth::user()->email]);
    }

    public function runCleanup()
    {
        $this->validate(['cleanupDays' => 'required|integer|min:1|max:365']);

        Artisan::call('games:cleanup', ['--days' => $this->cleanupDays]);
        $this->cleanupOutput = trim(Artisan::output());

        Log::info('Admin ran the games cleanup', ['days' => $this->cleanupDays, 'admin' => Auth::user()->email]);
        $this->resetPage();
    }

    public function render()
    {
        $games = Game::query()
            ->withCount(['players', 'agendas', 'agendas as voting_agendas_count' => fn ($query) => $query->where('status', 'voting')])
            ->addSelect(['votes_count' => Vote::selectRaw('count(*)')
                ->whereIn('agenda_id', Agenda::select('id')->whereColumn('game_id', 'games.id'))])
            ->withMax('agendas', 'created_at')
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('code', 'like', '%' . $this->search . '%')
                ->orWhere('name', 'like', '%' . $this->search . '%')));

        $this->applyFilters($games);

        match ($this->sort) {
            'activity' => $games->orderByDesc('agendas_max_created_at')->latest(),
            'players' => $games->orderByDesc('players_count')->latest(),
            default => $games->latest(),
        };

        $cleanupCount = is_numeric($this->cleanupDays) && $this->cleanupDays >= 1
            ? Game::inactiveFor((int) $this->cleanupDays)->count()
            : 0;

        return view('livewire.admin.games', [
            'games' => $games->paginate(20),
            'cleanupCount' => $cleanupCount,
            'mapFilters' => self::MAP_FILTERS,
            'expansionFilters' => collect(Game::EXPANSIONS)->map(fn ($expansion) => $expansion['label'])->all(),
            'optionFilters' => self::OPTION_FILTERS,
            'sorts' => self::SORTS,
            'filtered' => $this->search !== '' || $this->map !== '' || $this->expansion !== '' || $this->option !== '' || $this->sort !== 'newest',
        ]);
    }

    private function applyFilters(Builder $query): void
    {
        match ($this->map) {
            'none' => $query->whereNull('milty_draft_data'),
            // Milty drafts are the drafts without a source
            'milty' => $query->whereNotNull('milty_draft_data')->whereNull('milty_draft_data->source'),
            'tidraft', 'map_string' => $query->where('milty_draft_data->source', $this->map),
            default => null,
        };

        if ($settings = Game::EXPANSIONS[$this->expansion]['settings'] ?? null) {
            $query->where(fn ($query) => $this->whereAnySetting($query, $settings));
        }

        match ($this->option) {
            'voting' => $query->whereHas('agendas', fn ($query) => $query->where('status', 'voting')),
            'absol' => $query->where('absol_agendas', true),
            'managed_influence' => $query->where('managed_influence', true),
            'open_votes' => $query->where('hidden_agenda', false),
            'show_results' => $query->where('show_results', true),
            'representative_government' => $query->where('representative_government', true),
            // Only Thunder's Edge games have the Fracture
            'fracture' => $query->where('fracture_in_play', true)
                ->where(fn ($query) => $this->whereAnySetting($query, Game::EXPANSIONS['te']['settings'])),
            default => null,
        };
    }

    // Games whose map config has one of these settings turned on
    private function whereAnySetting(Builder $query, array $settings): void
    {
        foreach ($settings as $setting) {
            $query->orWhere("milty_draft_data->config->{$setting}", true);
        }
    }
}
