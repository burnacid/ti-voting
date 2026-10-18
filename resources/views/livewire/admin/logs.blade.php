<section class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
        <div>
            <h1 class="text-lg font-bold">Logs</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Entries older than {{ $retentionDays }} days are deleted every night.</p>
        </div>
        @if($entries->total() > 0)
            <button wire:click="deleteMatching"
                    wire:confirm="Delete {{ $entries->total() }} {{ ($level || $game || $search) ? 'matching' : '' }} log {{ Str::plural('entry', $entries->total()) }}? This cannot be undone."
                    class="w-full sm:w-auto shrink-0 text-sm font-medium bg-gray-200 hover:bg-red-600 hover:text-white dark:bg-gray-700 px-3 py-2 rounded-md transition-colors">
                Delete {{ ($level || $game || $search) ? 'matching' : 'all' }} ({{ $entries->total() }})
            </button>
        @endif
    </div>

    @php($inputClass = 'w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700')

    <!-- Filters -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <select wire:model.live="level" aria-label="Level" class="{{ $inputClass }}">
            <option value="">All levels ({{ $levelCounts->sum() }})</option>
            @foreach($levels as $logLevel)
                @if($levelCounts->has($logLevel))
                    <option value="{{ $logLevel }}">{{ ucfirst($logLevel) }} ({{ $levelCounts[$logLevel] }})</option>
                @endif
            @endforeach
        </select>
        <select wire:model.live="game" aria-label="Game" class="{{ $inputClass }}">
            <option value="">All games</option>
            @foreach($games as $code)
                <option value="{{ $code }}">{{ $code }}{{ $existingGames->has($code) ? '' : ' (deleted)' }}</option>
            @endforeach
            @if($game && !$games->contains(strtoupper($game)))
                <option value="{{ $game }}">{{ strtoupper($game) }} (no entries)</option>
            @endif
        </select>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search" aria-label="Search" class="{{ $inputClass }}">
    </div>

    <div class="flex items-center justify-between gap-3 mt-3 mb-4 text-sm text-gray-600 dark:text-gray-400">
        <span>{{ Number::format($entries->total()) }} {{ Str::plural('entry', $entries->total()) }}</span>
        @if($level || $game || $search)
            <button wire:click="clearFilters" class="text-blue-600 dark:text-blue-400 hover:underline">Clear filters</button>
        @endif
    </div>

    <!-- Entries -->
    <div class="divide-y divide-gray-200 dark:divide-gray-700 border-y border-gray-200 dark:border-gray-700">
        @forelse($entries as $entry)
            <article wire:key="entry-{{ $entry->id }}" @class([
                'py-3 pl-3 border-l-4',
                'border-red-500' => $entry->isError(),
                'border-yellow-400' => $entry->isWarning(),
                'border-transparent' => !$entry->isError() && !$entry->isWarning(),
            ])>
                <!-- Level, time and game -->
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                    <span @class([
                        'px-1.5 py-0.5 rounded font-semibold uppercase',
                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $entry->isError(),
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $entry->isWarning(),
                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' => !$entry->isError() && !$entry->isWarning(),
                    ])>{{ $entry->level }}</span>
                    <time datetime="{{ $entry->created_at->toIso8601String() }}" title="{{ $entry->created_at->diffForHumans() }}"
                          class="text-gray-500 dark:text-gray-400 font-mono">{{ $entry->created_at->format('Y-m-d H:i:s') }}</time>
                    @if($entry->game)
                        <button wire:click="$set('game', '{{ $entry->game }}')"
                                title="Show only this game"
                                class="px-1.5 py-0.5 rounded font-mono font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-800">
                            {{ $entry->game }}
                        </button>
                        @if($existingGames->has($entry->game))
                            <a href="{{ route('admin.games.show', $existingGames[$entry->game]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">game page</a>
                        @endif
                    @endif
                </div>

                <!-- Message -->
                <p class="mt-1 font-medium break-words">{{ $entry->message }}</p>

                <!-- Context -->
                @if($entry->context)
                    <dl class="mt-1.5 flex flex-wrap gap-1.5 text-xs">
                        @foreach($entry->context as $key => $value)
                            @php($display = match (true) {
                                $value === true => 'yes',
                                $value === false => 'no',
                                $value === null => '–',
                                is_scalar($value) => $value,
                                default => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                            })
                            <div class="inline-flex max-w-full rounded bg-gray-100 dark:bg-gray-700 overflow-hidden" title="{{ $display }}">
                                <dt class="px-1.5 py-0.5 text-gray-500 dark:text-gray-400">{{ $key }}</dt>
                                <dd class="px-1.5 py-0.5 font-mono truncate">{{ $display }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif

                <!-- Exception -->
                @if($entry->exception)
                    <div class="mt-1.5 text-sm">
                        <span class="font-mono text-red-700 dark:text-red-300">{{ $entry->exception['class'] }}</span>
                        @if(($entry->exception['message'] ?? null) && $entry->exception['message'] !== $entry->message)
                            <span class="break-words">{{ $entry->exception['message'] }}</span>
                        @endif
                        @if($entry->exception['location'] ?? null)
                            <div class="text-xs font-mono text-gray-500 dark:text-gray-400 break-all">{{ $entry->exception['location'] }}</div>
                        @endif
                    </div>
                @endif

                @if($entry->trace)
                    <details class="mt-1.5">
                        <summary class="cursor-pointer text-xs text-blue-600 dark:text-blue-400">Stack trace</summary>
                        <pre class="mt-1 text-xs text-gray-600 dark:text-gray-400 overflow-x-auto whitespace-pre bg-gray-50 dark:bg-gray-900 rounded p-2">{{ $entry->trace }}</pre>
                    </details>
                @endif
            </article>
        @empty
            <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">No log entries found.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $entries->links() }}
    </div>
</section>
