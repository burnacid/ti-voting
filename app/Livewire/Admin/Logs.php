<?php

namespace App\Livewire\Admin;

use App\Models\Game;
use App\Models\LogEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Logs extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public $level = '';

    #[Url(except: '')]
    public $game = '';

    #[Url(except: '')]
    public $search = '';

    public function updated($property)
    {
        if (in_array($property, ['level', 'game', 'search'])) {
            $this->resetPage();
        }
    }

    public function clearFilters()
    {
        $this->reset(['level', 'game', 'search']);
        $this->resetPage();
    }

    public function deleteMatching()
    {
        $deleted = $this->filtered()->delete();

        Log::info('Admin deleted log entries', [
            'entries' => $deleted,
            'filters' => array_filter(['level' => $this->level, 'game' => $this->game, 'search' => $this->search]) ?: null,
            'admin' => Auth::user()->email,
        ]);

        $this->clearFilters();
    }

    private function filtered(): Builder
    {
        return LogEntry::query()
            ->when($this->level, fn ($query) => $query->where('level', $this->level))
            ->when($this->game, fn ($query) => $query->where('game', strtoupper($this->game)))
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('message', 'like', '%' . $this->search . '%')
                ->orWhere('context', 'like', '%' . $this->search . '%')
                ->orWhere('exception', 'like', '%' . $this->search . '%')));
    }

    public function render()
    {
        $entries = $this->filtered()->latest('id')->paginate(50);
        $games = LogEntry::whereNotNull('game')->distinct()->orderBy('game')->pluck('game');

        return view('livewire.admin.logs', [
            'entries' => $entries,
            'levelCounts' => LogEntry::query()->selectRaw('level, COUNT(*) as total')->groupBy('level')->pluck('total', 'level'),
            'games' => $games,
            // Games that still exist link to their admin page
            'existingGames' => Game::whereIn('code', $games)->pluck('id', 'code'),
            'levels' => LogEntry::LEVELS,
            'retentionDays' => LogEntry::retentionDays(),
        ]);
    }
}
