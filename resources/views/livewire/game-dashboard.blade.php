<div class="space-y-6">
    <!-- Speaker Controls -->
    @if($player->is_speaker)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-yellow-800 dark:text-yellow-200">Speaker Controls</h2>
                <div class="flex space-x-2">
                    @if(!$currentAgenda)
                        <button wire:click="toggleCreateAgenda"
                                class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                            {{ $showCreateAgenda ? 'Cancel' : 'Create New Agenda' }}
                        </button>
                    @elseif($currentAgenda->status === 'voting')
                        @if($currentAgenda->allPlayersVoted())
                            <button wire:click="toggleSpeakerResults"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                                {{ $speakerViewResults ? 'Hide Results' : 'View Results' }}
                            </button>
                        @else
                            <button wire:click="refreshData"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                Check Votes
                            </button>
                        @endif
                        <button wire:click="endVoting"
                                wire:confirm="Are you sure you want to end voting? This will make results visible to all players."
                                class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                            End Voting
                        </button>
                    @endif
                </div>
            </div>

            <!-- Create Agenda Form -->
            @if($showCreateAgenda && !$currentAgenda)
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Create New Agenda</h3>

                    <form wire:submit="createAgenda" class="space-y-4">
                        <!-- Preset Agenda Selection -->
                        <div>
                            <label for="selectedPresetAgenda" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Select Preset Agenda (Optional)
                            </label>
                            <div class="flex space-x-2">
                                <select id="selectedPresetAgenda"
                                        wire:model.live="selectedPresetAgenda"
                                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">-- Create Custom Agenda --</option>
                                    @foreach($availableAgendas as $agenda)
                                        <option value="{{ $agenda['Name'] }}">
                                            {{ $agenda['Name'] }} ({{ ucfirst($agenda['Type']) }})
                                        </option>
                                    @endforeach
                                </select>
                                @if($selectedPresetAgenda)
                                    <button type="button"
                                            wire:click="clearPresetSelection"
                                            class="px-3 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md transition-colors">
                                        Clear
                                    </button>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Select a preset agenda to auto-fill the form, or create a custom one from scratch
                            </p>
                        </div>

                        <div>
                            <label for="newAgendaTitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Agenda Title
                            </label>
                            <input type="text"
                                   id="newAgendaTitle"
                                   wire:model="newAgendaTitle"
                                   placeholder="e.g., Elect New Speaker, Trade Agreement"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('newAgendaTitle') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="newAgendaDescription" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Description
                            </label>
                            <textarea id="newAgendaDescription"
                                      wire:model="newAgendaDescription"
                                      rows="3"
                                      placeholder="Describe what this agenda is about..."
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                            @error('newAgendaDescription') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Voting Type
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio"
                                           wire:model.live="agendaType"
                                           value="for_against"
                                           class="mr-2 text-blue-600">
                                    <span class="text-gray-900 dark:text-white">For / Against</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio"
                                           wire:model.live="agendaType"
                                           value="elect_player"
                                           class="mr-2 text-blue-600">
                                    <span class="text-gray-900 dark:text-white">Elect a Player</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio"
                                           wire:model.live="agendaType"
                                           value="elect_planet_any"
                                           class="mr-2 text-blue-600">
                                    <span class="text-gray-900 dark:text-white">Elect a Planet</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio"
                                           wire:model.live="agendaType"
                                           value="elect_planet_industrial"
                                           class="mr-2 text-blue-600">
                                    <span class="text-gray-900 dark:text-white">Elect an Industrial Planet</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio"
                                           wire:model.live="agendaType"
                                           value="elect_planet_hazardous"
                                           class="mr-2 text-blue-600">
                                    <span class="text-gray-900 dark:text-white">Elect a Hazardous Planet</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio"
                                           wire:model.live="agendaType"
                                           value="elect_planet_cultural"
                                           class="mr-2 text-blue-600">
                                    <span class="text-gray-900 dark:text-white">Elect a Cultural Planet</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio"
                                           wire:model.live="agendaType"
                                           value="elect_planet_non_home"
                                           class="mr-2 text-blue-600">
                                    <span class="text-gray-900 dark:text-white">Elect a Non-Home Planet other than Mecatol Rex</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio"
                                           wire:model.live="agendaType"
                                           value="custom"
                                           class="mr-2 text-blue-600">
                                    <span class="text-gray-900 dark:text-white">Custom Options</span>
                                </label>
                            </div>
                            @error('agendaType') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        @if($agendaType === 'custom')
                            <div>
                                <label for="customOptions" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Custom Options (comma separated)
                                </label>
                                <input type="text"
                                       id="customOptions"
                                       wire:model="customOptions"
                                       placeholder="Option 1, Option 2, Option 3"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                @error('customOptions') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        @if($agendaType === 'elect_player')
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    Players will be able to vote for any player currently in the game.
                                </p>
                            </div>
                        @endif

                        @if($agendaType === 'elect_planet_any')
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    Players will be able to vote for any planet in the game.
                                </p>
                            </div>
                        @endif

                        @if($agendaType === 'elect_planet_industrial')
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    Players will be able to vote for any industrial planet in the game.
                                </p>
                            </div>
                        @endif

                        @if($agendaType === 'elect_planet_hazardous')
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    Players will be able to vote for any hazardous planet in the game.
                                </p>
                            </div>
                        @endif

                        @if($agendaType === 'elect_planet_cultural')
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    Players will be able to vote for any cultural planet in the game.
                                </p>
                            </div>
                        @endif

                        @if($agendaType === 'elect_planet_non_home')
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    Players will be able to vote for any non-home planet in the game except Mecatol Rex.
                                </p>
                            </div>
                        @endif

                        @if(str_starts_with($agendaType, 'elect_planet_'))
                            <div>
                                <label for="customTileNumbers" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Additional Tile Numbers (comma separated, optional)
                                </label>
                                <input type="text"
                                       id="customTileNumbers"
                                       wire:model="customTileNumbers"
                                       placeholder="18, 25, 32"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Add specific tile numbers to include their planets in the voting options
                                </p>
                            </div>
                        @endif

                        <div class="flex space-x-3">
                            <button type="submit"
                                    class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                                Create Agenda
                            </button>
                            <button type="button"
                                    wire:click="toggleCreateAgenda"
                                    class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    @endif

    <!-- Players List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Players</h2>
            <button wire:click="refreshData"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
        </div>

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
                        <button wire:click="transferSpeaker('{{ $gamePlayer->id }}')"
                                wire:confirm="Are you sure you want to transfer the Speaker token to {{ $gamePlayer->name }}?"
                                class="text-xs bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded transition-colors">
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
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $currentAgenda->title }}</h2>
                    <p class="text-gray-600 dark:text-gray-300 mt-1">{!! nl2br($currentAgenda->formatted_description ) !!}</p>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $currentAgenda->status === 'voting' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                        {{ ucfirst($currentAgenda->status) }}
                    </span>
                </div>
            </div>

            @if($currentAgenda->status === 'voting' && !$hasVoted)
                <!-- Voting Form -->
                <form wire:submit="submitVote" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Choose your option:
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            @foreach($currentAgenda->options as $option)
                                <label class="flex items-center p-2 rounded-md
                                    {{ $option === 'Abstain' ? 'bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 border border-red-200 dark:border-red-800' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}
                                    transition-colors">
                                    <input type="radio"
                                           wire:model.live="selectedOption"
                                           value="{{ $option }}"
                                           class="{{ $option === 'Abstain' ? 'text-red-600' : 'text-blue-600' }} mr-2">
                                    <span class="{{ $option === 'Abstain' ? 'text-red-800 dark:text-red-200' : 'text-gray-900 dark:text-white' }}">{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedOption') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    @if($selectedOption !== 'Abstain')
                    <div>
                        <label for="influenceSpent" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Influence to spend (1-99):
                        </label>
                        <input type="number"
                               id="influenceSpent"
                               wire:model="influenceSpent"
                               min="1"
                               max="99"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        @error('influenceSpent') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    @endif

                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                        Submit Vote
                    </button>
                </form>
            @elseif($hasVoted && $currentAgenda->status === 'voting')
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <div class="flex items-center">
                        <span class="text-green-800 dark:text-green-200 font-medium">You have voted on this agenda</span>
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
            @if($speakerViewResults || $currentAgenda->status === 'completed')
                <div class="border-t pt-4 mt-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Voting Results</h3>
                    <div class="space-y-3">
                        @foreach($voteResults as $option => $result)
                            @if($result['influence'] > 0)
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
                                        {{ $result['influence'] }} influence
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('option-selected', (event) => {
            const isAbstain = event.detail.option === 'Abstain';
            const influenceInput = document.getElementById('influenceSpent');

            if (isAbstain) {
                influenceInput.value = '0';
                influenceInput.disabled = true;
            } else {
                influenceInput.disabled = false;
            }
        });
    });
</script>
