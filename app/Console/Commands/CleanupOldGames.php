<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Agenda;
use App\Models\Vote;
use App\Models\Player;
use Illuminate\Console\Command;

class CleanupOldGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:cleanup {--days=7 : Number of days to retain games}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete games older than the specified number of days (default: 7 days)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        // Get old games
        $oldGames = Game::where('created_at', '<', $cutoffDate)->get();
        $gamesToDelete = $oldGames->count();

        if ($gamesToDelete === 0) {
            $this->info("No games found older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')}).");
            return self::SUCCESS;
        }

        $this->info("Found {$gamesToDelete} game(s) to delete. Starting cleanup...");

        $deletedVotes = 0;
        $deletedAgendas = 0;
        $deletedPlayers = 0;
        $deletedGames = 0;

        foreach ($oldGames as $game) {
            // Delete votes associated with game's agendas
            $deletedVotes += Vote::whereIn('agenda_id', $game->agendas()->pluck('id'))->delete();

            // Delete agendas
            $deletedAgendas += Agenda::where('game_id', $game->id)->delete();

            // Delete players
            $deletedPlayers += Player::where('game_id', $game->id)->delete();

            $deletedGames += $game->delete();
        }

        $this->info("Cleanup completed successfully!");
        $this->line("  • Games deleted: {$deletedGames}");
        $this->line("  • Players deleted: {$deletedPlayers}");
        $this->line("  • Agendas deleted: {$deletedAgendas}");
        $this->line("  • Votes deleted: {$deletedVotes}");
        $this->info("Cutoff date: {$cutoffDate->format('Y-m-d H:i:s')}");

        return self::SUCCESS;
    }
}

