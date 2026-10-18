<x-admin-layout title="Dashboard">
    <!-- Totals -->
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach($stats as $label => $value)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">{{ $label }}</div>
                <div class="text-2xl sm:text-3xl font-bold tabular-nums">{{ Number::format($value) }}</div>
            </div>
        @endforeach
    </section>

    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">Active in the last 24 hours</div>
            <div class="text-xl font-bold tabular-nums">{{ $activeGames }} {{ Str::plural('game', $activeGames) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">Agendas open right now</div>
            <div class="text-xl font-bold tabular-nums">{{ $votingAgendas }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">Players per game</div>
            <div class="text-xl font-bold tabular-nums">{{ $averagePlayers }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">Created with Milty's Draft</div>
            <div class="text-xl font-bold tabular-nums">
                {{ $miltyGames }}
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">/ {{ $gameCount }}</span>
            </div>
        </div>
    </section>

    <!-- Games per day -->
    @php($maxPerDay = max(1, max($gamesPerDay)))
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 sm:p-6">
        <div class="flex items-baseline justify-between gap-3 mb-4">
            <h2 class="text-lg font-bold">Games created per day</h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">Last {{ count($gamesPerDay) }} days</span>
        </div>

        <div class="relative h-40" role="img" aria-label="Bar chart of games created per day over the last {{ count($gamesPerDay) }} days">
            <!-- Baseline and top gridline -->
            <div class="absolute inset-x-0 top-0 border-t border-dashed border-gray-200 dark:border-gray-700"></div>
            <span class="absolute right-0 -top-5 text-xs text-gray-500 dark:text-gray-400 tabular-nums">{{ $maxPerDay }}</span>
            <div class="absolute inset-x-0 bottom-0 border-t border-gray-300 dark:border-gray-600"></div>

            <div class="absolute inset-0 flex items-end gap-0.5">
                @foreach($gamesPerDay as $day => $total)
                    <div class="group relative flex-1 h-full flex items-end">
                        <div class="w-full rounded-t bg-blue-600 dark:bg-blue-400 group-hover:bg-blue-700 dark:group-hover:bg-blue-300"
                             style="height: {{ $total ? max(2, $total / $maxPerDay * 100) : 0 }}%"></div>
                        <div class="pointer-events-none absolute bottom-full {{ $loop->index < 3 ? 'left-0' : ($loop->remaining < 3 ? 'right-0' : 'left-1/2 -translate-x-1/2') }} mb-1 hidden group-hover:block whitespace-nowrap rounded-md bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-xs px-2 py-1 shadow z-10">
                            {{ \Illuminate\Support\Carbon::parse($day)->format('D j M') }}: <strong>{{ $total }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="flex justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
            <span>{{ \Illuminate\Support\Carbon::parse(array_key_first($gamesPerDay))->format('j M') }}</span>
            <span>Today</span>
        </div>

        <table class="sr-only">
            <caption>Games created per day</caption>
            <thead><tr><th>Day</th><th>Games</th></tr></thead>
            <tbody>
                @foreach($gamesPerDay as $day => $total)
                    <tr><td>{{ $day }}</td><td>{{ $total }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top agendas -->
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 sm:p-6">
            <h2 class="text-lg font-bold mb-3">Most played agendas</h2>
            @forelse($topAgendas as $agenda)
                <div class="flex items-center justify-between gap-3 py-1.5 border-b border-gray-100 dark:border-gray-700 last:border-0 text-sm">
                    <span class="truncate">{{ $agenda->title }}</span>
                    <span class="tabular-nums text-gray-600 dark:text-gray-400">{{ $agenda->total }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No agendas yet.</p>
            @endforelse
        </section>

        <!-- Recent errors and cleanup -->
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 sm:p-6">
            <div class="flex items-baseline justify-between gap-3 mb-3">
                <h2 class="text-lg font-bold">Recent errors</h2>
                <a href="{{ route('admin.logs', ['level' => 'error']) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">All logs</a>
            </div>
            @forelse($recentErrors as $error)
                <div class="py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400 font-mono">
                        {{ $error->created_at->format('Y-m-d H:i:s') }}
                        @if($error->game)
                            <a href="{{ route('admin.logs', ['game' => $error->game]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $error->game }}</a>
                        @endif
                    </div>
                    <p class="text-sm break-words line-clamp-2">{{ $error->message }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No errors in the last {{ \App\Models\LogEntry::retentionDays() }} days.</p>
            @endforelse

            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400">
                {{ $cleanupCount }} {{ Str::plural('game', $cleanupCount) }} without activity for {{ $retentionDays }} days will be removed by the nightly cleanup.
                <a href="{{ route('admin.games') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Manage games</a>
            </div>
        </section>
    </div>
</x-admin-layout>
