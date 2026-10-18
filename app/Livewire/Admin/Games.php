<?php

namespace App\Livewire\Admin;

use App\Models\Game;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Games extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public $search = '';

    public $cleanupDays = 7;
    public $cleanupOutput = null;

    public function updatedSearch()
    {
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
            ->withCount(['players', 'agendas'])
            ->withMax('agendas', 'created_at')
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('code', 'like', '%' . $this->search . '%')
                ->orWhere('name', 'like', '%' . $this->search . '%')))
            ->latest()
            ->paginate(20);

        $cleanupCount = is_numeric($this->cleanupDays) && $this->cleanupDays >= 1
            ? Game::olderThan((int) $this->cleanupDays)->count()
            : 0;

        return view('livewire.admin.games', [
            'games' => $games,
            'cleanupCount' => $cleanupCount,
        ]);
    }
}
