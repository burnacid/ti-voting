@props(['agenda', 'predictions', 'player', 'players', 'canPredict' => false, 'outcome' => null])

@php
    $selectClass = 'w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white';
@endphp

<!-- Outcomes predicted aloud, e.g. with a Rider, shown to every player -->
<section class="border-t pt-4 mt-4">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Predictions</h3>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
        A player who predicts an outcome, for example with a Rider, cannot vote on this agenda.
    </p>

    @if($predictions->isNotEmpty())
        <ul role="list" class="space-y-2">
            @foreach($predictions as $prediction)
                <li wire:key="prediction-{{ $prediction->id }}"
                    class="flex items-start gap-3 p-3 rounded-lg border border-purple-200 bg-purple-50/60 dark:border-purple-900 dark:bg-purple-950/30">
                    <x-player-avatar :player="$prediction->player" class="w-8! h-8! text-xs!" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-gray-900 dark:text-white">
                            <span class="font-semibold">{{ $prediction->player->name }}</span>
                            predicts <span class="font-semibold">{{ $prediction->option }}</span>
                        </p>
                        <p class="text-xs font-medium text-purple-800 dark:text-purple-300">{{ $prediction->source_label }}</p>
                        @if($prediction->reward)
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">If correct: {{ $prediction->reward }}</p>
                        @endif
                        @if($outcome !== null && $prediction->option === $outcome && $prediction->showsVoters())
                            @php($voters = $agenda->playersWhoVoted($outcome))
                            <p class="text-xs text-gray-900 dark:text-white mt-1">
                                <span class="font-medium">Voted {{ $outcome }}:</span>
                                {{ $voters->isEmpty() ? 'nobody' : $voters->pluck('name')->implode(', ') }}
                            </p>
                        @endif
                    </div>

                    @if($outcome !== null)
                        @if($prediction->option === $outcome)
                            <span class="shrink-0 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">Correct</span>
                        @else
                            <span class="shrink-0 px-2 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Wrong</span>
                        @endif
                    @elseif($canPredict && ($player->is_speaker || $prediction->player_id === $player->id))
                        <button wire:click="removePrediction('{{ $prediction->id }}')"
                                wire:confirm="Remove the prediction of {{ $prediction->player->name }}?"
                                class="shrink-0 text-xs font-semibold px-3 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 transition-colors">
                            Remove
                        </button>
                    @endif
                </li>
            @endforeach
        </ul>
    @elseif($canPredict)
        <p class="text-sm text-gray-500 dark:text-gray-400">No predictions yet.</p>
    @endif

    @if($canPredict)
        <form wire:submit="makePrediction" class="mt-4 space-y-3">
            @if($player->is_speaker)
                <div>
                    <label for="predictionPlayerId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Player</label>
                    <select id="predictionPlayerId" wire:model="predictionPlayerId" class="{{ $selectClass }}">
                        <option value="">Me ({{ $player->name }})</option>
                        @foreach($players->reject(fn ($gamePlayer) => $gamePlayer->id === $player->id) as $gamePlayer)
                            <option value="{{ $gamePlayer->id }}">{{ $gamePlayer->name }}{{ $gamePlayer->faction ? " ({$gamePlayer->faction})" : '' }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="predictionSource" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Card or ability</label>
                    <select id="predictionSource" wire:model="predictionSource" class="{{ $selectClass }}">
                        <option value="">Choose a card</option>
                        @foreach(\App\Models\Prediction::SOURCES as $key => $source)
                            <option value="{{ $key }}">{{ $source['label'] }}</option>
                        @endforeach
                    </select>
                    @error('predictionSource') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="predictionOption" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Predicted outcome</label>
                    <select id="predictionOption" wire:model="predictionOption" class="{{ $selectClass }}">
                        <option value="">Choose an outcome</option>
                        @foreach(array_diff($agenda->options, ['Abstain']) as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('predictionOption') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <button type="submit"
                    class="w-full sm:w-auto bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                Predict
            </button>
        </form>
    @endif
</section>
