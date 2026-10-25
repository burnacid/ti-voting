<div class="space-y-6">
    <!-- Cleanup -->
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 sm:p-6">
        <h2 class="text-lg font-bold mb-1">Cleanup</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Games are deleted automatically every night at 02:00 once they have had no activity for {{ \App\Models\Game::retentionDays() }} days. You can also run the cleanup now.
        </p>

        <form wire:submit="runCleanup" class="flex flex-col sm:flex-row sm:items-end gap-3">
            <div>
                <label for="cleanupDays" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Delete games inactive for (days)</label>
                <input type="number" id="cleanupDays" wire:model.live.debounce.300ms="cleanupDays" min="1" max="365"
                       class="w-full sm:w-32 px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700">
            </div>
            <button type="submit"
                    @disabled($cleanupCount === 0)
                    wire:confirm="Delete {{ $cleanupCount }} game(s) without activity for {{ $cleanupDays }} days, with their players, agendas and votes?"
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
        @php($filterClass = 'w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700')
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
            <h2 class="text-lg font-bold">Games <span class="text-gray-500 dark:text-gray-400 font-normal">({{ $games->total() }})</span></h2>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search code or name" aria-label="Search code or name"
                   class="{{ $filterClass }} sm:w-64">
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-2">
            <label class="block">
                <span class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Map</span>
                <select wire:model.live="map" class="{{ $filterClass }}">
                    <option value="">Any map</option>
                    @foreach($mapFilters as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Expansion</span>
                <select wire:model.live="expansion" class="{{ $filterClass }}">
                    <option value="">Any expansion</option>
                    @foreach($expansionFilters as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Option</span>
                <select wire:model.live="option" class="{{ $filterClass }}">
                    <option value="">Any option</option>
                    @foreach($optionFilters as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Sort</span>
                <select wire:model.live="sort" class="{{ $filterClass }}">
                    @foreach($sorts as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        @if($filtered)
            <button type="button" wire:click="clearFilters" class="text-sm text-blue-600 dark:text-blue-400 hover:underline mb-2">Clear filters</button>
        @endif

        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($games as $game)
                <div wire:key="game-{{ $game->id }}" class="py-3 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                    <a href="{{ route('admin.games.show', $game) }}" class="flex-1 min-w-0 group">
                        <div class="font-semibold truncate group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ $game->name }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-mono">{{ $game->code }}</span>
                            · {{ $game->players_count }} {{ Str::plural('player', $game->players_count) }}
                            · {{ $game->agendas_count }} {{ Str::plural('agenda', $game->agendas_count) }}
                            · {{ $game->votes_count }} {{ Str::plural('vote', $game->votes_count) }}
                        </div>
                        <x-admin-game-badges :game="$game" :voting="$game->voting_agendas_count > 0" class="my-1" />
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
