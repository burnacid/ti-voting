<div class="space-y-6">
    {{-- If you look to others for fulfillment, you will never truly be fulfilled. --}}
    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <!-- Players List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Players</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($players as $gamePlayer)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ $gamePlayer->name }}
                            @if($gamePlayer->is_speaker)
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    Speaker
                                </span>
                            @endif
                        </div>
                        @if($gamePlayer->faction)
                            <div class="text-sm text-gray-600 dark:text-gray-300">{{ $gamePlayer->faction }}</div>
                        @endif
                        @if($currentAgenda && $gamePlayer->hasVotedOn($currentAgenda))
                            <div class="text-xs text-green-600 dark:text-green-400">✓ Voted</div>
                        @endif
                    </div>

                    @if($player->is_speaker && $gamePlayer->id !== $player->id)
                        <button wire:click="transferSpeaker({{ $gamePlayer->id }})"
                                class="text-xs bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded">
                            Make Speaker
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Current Agenda -->
    @if($currentAgenda)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $currentAgenda->title }}</h2>
                    <p class="text-gray-600 dark:text-gray-300 mt-2">{{ $currentAgenda->description }}</p>
                </div>
                @if($player->is_speaker)
                    <div class="flex space-x-2">
                        <button wire:click="toggleResults"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                            {{ $showResults ? 'Hide' : 'Show' }} Results
                        </button>
                        <button wire:click="endVoting"
                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                            End Voting
                        </button>
                    </div>
                @endif
            </div>

            <!-- Voting Interface -->
            @if($currentAgenda->status === 'voting' && !$hasVoted)
                <div class="border-t pt-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Cast Your Vote</h3>
                    <form wire:submit="submitVote" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Choose Option
                            </label>
                            <div class="space-y-2">
                                @foreach($currentAgenda->options as $option)
                                    <label class="flex items-center">
                                        <input type="radio"
                                               wire:model="selectedOption"
                                               value="{{ $option }}"
                                               class="mr-2 text-blue-600">
                                        <span class="text-gray-900 dark:text-white">{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedOption') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="influenceSpent" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Influence Tokens to Spend
                            </label>
                            <input type="number"
                                   id="influenceSpent"
                                   wire:model="influenceSpent"
                                   min="0"
                                   max="99"
                                   class="w-20 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('influenceSpent') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md">
                            Submit Vote
                        </button>
                    </form>
                </div>
            @elseif($hasVoted)
                <div class="border-t pt-4">
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-green-800 dark:text-green-200 font-medium">You have voted on this agenda</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Vote Progress -->
            <div class="border-t pt-4 mt-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Voting Progress</span>
                    <span class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $currentAgenda->votes()->count() }} / {{ $players->count() }} players voted
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full"
                         style="width: {{ $players->count() > 0 ? ($currentAgenda->votes()->count() / $players->count()) * 100 : 0 }}%"></div>
                </div>
            </div>

            <!-- Results (Speaker Only or when voting ended) -->
            @if($voteResults)
                <div class="border-t pt-4 mt-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Voting Results</h3>
                    <div class="space-y-3">
                        @foreach($voteResults as $option => $result)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $option }}</span>
                                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ $result['percentage'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full"
                                         style="width: {{ $result['percentage'] }}%"></div>
                                </div>
                                <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $result['count'] }} votes
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
