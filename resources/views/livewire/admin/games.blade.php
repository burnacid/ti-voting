<div class="space-y-6">
    <!-- Cleanup -->
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 sm:p-6">
        <h2 class="text-lg font-bold mb-1">Cleanup</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Games are deleted automatically every night at 02:00 once they are older than 7 days. You can also run the cleanup now.
        </p>

        <form wire:submit="runCleanup" class="flex flex-col sm:flex-row sm:items-end gap-3">
            <div>
                <label for="cleanupDays" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Delete games older than (days)</label>
                <input type="number" id="cleanupDays" wire:model.live.debounce.300ms="cleanupDays" min="1" max="365"
                       class="w-full sm:w-32 px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700">
            </div>
            <button type="submit"
                    @disabled($cleanupCount === 0)
                    wire:confirm="Delete {{ $cleanupCount }} game(s) older than {{ $cleanupDays }} days, with their players, agendas and votes?"
                    class="w-full sm:w-auto bg-red-600 hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                Delete {{ $cleanupCount }} {{ Str::plural('game', $cleanupCount) }}
            </button>
        </form>
        @error('cleanupDays') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

        @if($cleanupOutput)
            <pre class="mt-4 text-xs bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-3 overflow-x-auto">{{ $cleanupOutput }}</pre>
        @endif
    </section>

    <!-- Games -->
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h2 class="text-lg font-bold">Games <span class="text-gray-500 dark:text-gray-400 font-normal">({{ $games->total() }})</span></h2>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search code or name"
                   class="w-full sm:w-64 px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700">
        </div>

        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($games as $game)
                <div wire:key="game-{{ $game->id }}" class="py-3 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                    <a href="{{ route('admin.games.show', $game) }}" class="flex-1 min-w-0 group">
                        <div class="font-semibold truncate group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ $game->name }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-mono">{{ $game->code }}</span>
                            · {{ $game->players_count }} {{ Str::plural('player', $game->players_count) }}
                            · {{ $game->agendas_count }} {{ Str::plural('agenda', $game->agendas_count) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Created {{ $game->created_at->diffForHumans() }}
                            @if($game->agendas_max_created_at)
                                · last agenda {{ \Illuminate\Support\Carbon::parse($game->agendas_max_created_at)->diffForHumans() }}
                            @endif
                        </div>
                    </a>
                    <button wire:click="deleteGame('{{ $game->id }}')"
                            wire:confirm="Delete {{ $game->name }} ({{ $game->code }}) with its players, agendas and votes?"
                            class="self-start sm:self-center text-xs font-medium bg-gray-200 hover:bg-red-600 hover:text-white dark:bg-gray-700 px-3 py-2 rounded-md transition-colors">
                        Delete
                    </button>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">No games found.</p>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $games->links() }}
        </div>
    </section>
</div>
