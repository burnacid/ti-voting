@props(['draws', 'poolSize', 'isSpeaker'])

{{-- Star Chart: draw a random system tile that is not on the map yet. Shown to every player --}}
<section class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Star Chart</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Draw a random system tile that is not on the map yet.
                {{ $poolSize }} {{ Str::plural('tile', $poolSize) }} left.
            </p>
        </div>
        <button wire:click="drawStarChart"
                wire:confirm="Draw a random system tile with Star Chart? Everyone in the game sees the result."
                wire:loading.attr="disabled"
                @disabled($poolSize === 0)
                class="w-full sm:w-auto shrink-0 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
            Draw a system tile
        </button>
    </div>

    @forelse($draws as $draw)
        <div wire:key="star-chart-{{ $draw['tile'] }}"
             @class([
                 'flex items-start gap-4 rounded-lg p-3',
                 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' => $loop->first,
                 'mt-2 bg-gray-50 dark:bg-gray-700/50' => !$loop->first,
             ])>
            <!-- Tile number on a hex, like the number printed on the system tile -->
            <div @class(['relative shrink-0 flex items-center justify-center', 'w-16 h-16' => $loop->first, 'w-11 h-11' => !$loop->first])>
                <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100" aria-hidden="true">
                    <polygon points="50,3 93,27 93,73 50,97 7,73 7,27" fill="#0b1828" stroke="#f0a02e" stroke-width="4" stroke-linejoin="round"/>
                </svg>
                <span @class(['relative font-mono font-bold text-white', 'text-lg' => $loop->first, 'text-xs' => !$loop->first])>{{ $draw['tile'] }}</span>
            </div>

            <div class="flex-1 min-w-0">
                @forelse($draw['planets'] as $planet)
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 {{ $loop->parent->first ? '' : 'text-sm' }}">
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $planet['name'] }}</span>
                        <span class="inline-flex rounded overflow-hidden text-xs font-semibold" title="Resources / influence">
                            <span class="px-1.5 py-0.5 bg-amber-100 text-amber-900 dark:bg-amber-900 dark:text-amber-100">{{ $planet['resources'] }}</span>
                            <span class="px-1.5 py-0.5 bg-sky-100 text-sky-900 dark:bg-sky-900 dark:text-sky-100">{{ $planet['influence'] }}</span>
                        </span>
                        @foreach((array) ($planet['trait'] ?? []) as $trait)
                            <span class="text-xs text-gray-600 dark:text-gray-400">{{ ucfirst($trait) }}</span>
                        @endforeach
                        @if(!empty($planet['legendary']))
                            <span class="text-xs font-semibold text-purple-700 dark:text-purple-300">Legendary</span>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-400">No planets</p>
                @endforelse
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Drawn by {{ $draw['player'] }} · {{ \Illuminate\Support\Carbon::parse($draw['drawn_at'])->diffForHumans() }}
                </p>
            </div>

            @if($isSpeaker)
                <button wire:click="removeStarChartTile('{{ $draw['tile'] }}')"
                        wire:confirm="Undo drawing tile {{ $draw['tile'] }}? It goes back into the Star Chart pool."
                        aria-label="Undo drawing tile {{ $draw['tile'] }}"
                        title="Undo this draw"
                        class="shrink-0 p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">No system tiles drawn yet.</p>
    @endforelse
</section>
