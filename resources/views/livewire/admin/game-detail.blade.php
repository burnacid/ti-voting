<div class="space-y-6">
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 sm:p-6">
        <a href="{{ route('admin.games') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">&larr; All games</a>

        <div class="mt-2 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-bold break-words">{{ $game->name }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-mono font-semibold">{{ $game->code }}</span>
                    · created {{ $game->created_at->format('Y-m-d H:i') }} ({{ $game->created_at->diffForHumans() }})
                </p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    @if($game->milty_url)
                        <a href="{{ $game->milty_url }}" target="_blank" rel="noopener noreferrer"
                           class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 hover:underline">Milty draft</a>
                    @endif
                    @if($game->isThundersEdgeGame())
                        <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                            Thunder's Edge{{ $game->fracture_in_play ? ' · Fracture in play' : '' }}
                        </span>
                    @endif
                    @if($game->representative_government)
                        <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Representative Government</span>
                    @endif
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                <a href="{{ route('admin.logs', ['game' => $game->code]) }}"
                   class="w-full sm:w-auto text-center bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                    View logs
                </a>
                <button wire:click="deleteGame"
                        wire:confirm="Delete {{ $game->name }} ({{ $game->code }}) with its players, agendas and votes?"
                        class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                    Delete game
                </button>
            </div>
        </div>
    </section>

    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 sm:p-6">
        <h2 class="text-lg font-bold mb-3">Players ({{ $players->count() }})</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($players as $gamePlayer)
                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="font-semibold flex flex-wrap items-center gap-2">
                        {{ $gamePlayer->name }}
                        @if($gamePlayer->is_speaker)
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Speaker</span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ $gamePlayer->faction ?? 'No faction' }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Joined {{ $gamePlayer->created_at->diffForHumans() }}</div>

                    <div class="mt-2 flex flex-wrap gap-2">
                        @unless($gamePlayer->is_speaker)
                            <button wire:click="makeSpeaker('{{ $gamePlayer->id }}')"
                                    wire:confirm="Make {{ $gamePlayer->name }} the Speaker?"
                                    class="text-xs font-medium bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-md transition-colors">
                                Make Speaker
                            </button>
                        @endunless
                        <button wire:click="removePlayer('{{ $gamePlayer->id }}')"
                                wire:confirm="Remove {{ $gamePlayer->name }} from the game? Their votes are deleted too."
                                class="text-xs font-medium bg-gray-200 hover:bg-red-600 hover:text-white dark:bg-gray-600 px-3 py-2 rounded-md transition-colors">
                            Remove
                        </button>
                    </div>
                    <div class="mt-2">
                        <x-rejoin-link :player="$gamePlayer" />
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 sm:p-6">
        <h2 class="text-lg font-bold mb-3">Agendas ({{ $agendas->count() }})</h2>

        <div class="space-y-3">
            @forelse($agendas as $agenda)
                <details class="group border border-gray-200 dark:border-gray-700 rounded-lg">
                    <summary class="cursor-pointer list-none p-3 flex flex-wrap items-center gap-2">
                        <span class="font-semibold flex-1 min-w-0 break-words">{{ $agenda->title }}</span>
                        <span @class([
                            'px-2 py-0.5 rounded-full text-xs font-medium',
                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $agenda->status === 'voting',
                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' => $agenda->status !== 'voting',
                        ])>{{ $agenda->reveal_step_label ?? ucfirst($agenda->status) }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $agenda->votes->count() }} {{ Str::plural('vote', $agenda->votes->count()) }} · {{ $agenda->created_at->format('Y-m-d H:i') }}</span>
                    </summary>

                    <div class="px-3 pb-3 text-sm">
                        @if($agenda->votes->isEmpty())
                            <p class="text-gray-500 dark:text-gray-400">No votes.</p>
                        @else
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs text-gray-500 dark:text-gray-400">
                                        <th class="py-1 font-medium">Player</th>
                                        <th class="py-1 font-medium">Option</th>
                                        <th class="py-1 font-medium text-right">Influence</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach($agenda->votes->sortByDesc('influence_spent') as $vote)
                                        <tr>
                                            <td class="py-1.5 pr-2">{{ $vote->player?->name ?? 'Removed player' }}</td>
                                            <td class="py-1.5 pr-2">{{ $vote->option }}</td>
                                            <td class="py-1.5 text-right tabular-nums">{{ $vote->influence_spent }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </details>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No agendas yet.</p>
            @endforelse
        </div>
    </section>
</div>
