<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Game;
use App\Models\LogEntry;
use App\Models\Player;
use App\Models\Vote;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    private const CHART_DAYS = 14;

    public function __invoke()
    {
        $gameCount = Game::count();
        $since = now()->subDay();

        return view('admin.dashboard', [
            'stats' => [
                'Games' => $gameCount,
                'Players' => Player::count(),
                'Agendas' => Agenda::count(),
                'Votes' => Vote::count(),
            ],
            'activeGames' => Game::where('created_at', '>=', $since)
                ->orWhereHas('agendas', fn ($query) => $query->where('created_at', '>=', $since))
                ->count(),
            'votingAgendas' => Agenda::where('status', 'voting')->count(),
            'averagePlayers' => $gameCount ? round(Player::count() / $gameCount, 1) : 0,
            'miltyGames' => Game::whereNotNull('milty_draft_data')->count(),
            'gameCount' => $gameCount,
            'cleanupCount' => Game::olderThan(7)->count(),
            'gamesPerDay' => $this->gamesPerDay(),
            'topAgendas' => Agenda::query()
                ->select('title')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('title')
                ->orderByDesc('total')
                ->limit(8)
                ->get(),
            'recentErrors' => LogEntry::whereIn('level', LogEntry::ERROR_LEVELS)->latest('id')->limit(5)->get(),
        ]);
    }

    /**
     * Number of games created on each of the last days, oldest first, keyed by date.
     */
    private function gamesPerDay(): array
    {
        $start = now()->subDays(self::CHART_DAYS - 1)->startOfDay();

        $counts = Game::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(CarbonPeriod::create($start, now()->startOfDay()))
            ->mapWithKeys(fn ($day) => [$day->toDateString() => (int) ($counts[$day->toDateString()] ?? 0)])
            ->all();
    }
}
