<div class="space-y-6">
    <!-- Speaker Controls -->
    @if($player->is_speaker)
        @php
            $hasMilty = $game->milty_draft_data !== null;
            $inputClass = 'w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white';
            $votingTypes = [
                'for_against' => ['label' => 'For / Against', 'planet' => false, 'hint' => null],
                'elect_player' => ['label' => 'Elect a Player', 'planet' => false, 'hint' => 'Players will be able to vote for any player currently in the game.'],
                'elect_planet_any' => ['label' => 'Elect a Planet', 'planet' => true, 'hint' => 'Players will be able to vote for any planet in the game.'],
                'elect_planet_industrial' => ['label' => 'Elect an Industrial Planet', 'planet' => true, 'hint' => 'Players will be able to vote for any industrial planet in the game.'],
                'elect_planet_hazardous' => ['label' => 'Elect a Hazardous Planet', 'planet' => true, 'hint' => 'Players will be able to vote for any hazardous planet in the game.'],
                'elect_planet_cultural' => ['label' => 'Elect a Cultural Planet', 'planet' => true, 'hint' => 'Players will be able to vote for any cultural planet in the game.'],
                'elect_planet_non_home' => ['label' => 'Elect a Non-Home Planet other than Mecatol Rex', 'planet' => true, 'hint' => 'Players will be able to vote for any non-home planet in the game except Mecatol Rex.'],
                'custom' => ['label' => 'Custom Options', 'planet' => false, 'hint' => null],
            ];
        @endphp
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 sm:p-6 space-y-4">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-yellow-800 dark:text-yellow-200">Speaker Controls</h2>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                        @if($currentAgenda?->status === 'voting' && $currentAgenda->reveal_step)
                            Resolve action cards before voting opens.
                        @elseif($currentAgenda?->status === 'voting')
                            Voting is open.
                        @else
                            Reveal the next agenda when you are ready.
                        @endif
                    </p>
                </div>

                @if($currentAgenda?->status !== 'voting')
                    @unless($showCreateAgenda)
                        <button wire:click="toggleCreateAgenda"
                                class="w-full sm:w-auto bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                            Create New Agenda
                        </button>
                    @endunless
                @elseif($currentAgenda->reveal_step)
                    <button wire:click="discardAgenda"
                            wire:confirm="Discard this agenda, for example because it was vetoed? You can then reveal the next agenda."
                            class="w-full sm:w-auto bg-gray-600 hover:bg-gray-700 active:bg-gray-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                        Discard Agenda
                    </button>
                @else
                    <button wire:click="endVoting"
                            wire:confirm="Are you sure you want to end voting? The results will only be visible to you."
                            class="w-full sm:w-auto bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                        End Voting
                    </button>
                @endif
            </div>

            <!-- Thunder's Edge: The Fracture -->
            @if($game->isThundersEdgeGame())
                <label class="flex items-center justify-between gap-4 bg-white/70 dark:bg-gray-800/60 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3 cursor-pointer">
                    <span>
                        <span class="block font-medium text-gray-900 dark:text-white">The Fracture is in play</span>
                        <span class="block text-sm text-gray-600 dark:text-gray-400">
                            Adds Cocytus, Lethe, Phlegethon and Styx to planet agendas.
                        </span>
                    </span>
                    <input type="checkbox"
                           wire:click="toggleFracture"
                           @checked($game->fracture_in_play)
                           class="h-6 w-6 shrink-0 text-blue-600 rounded">
                </label>
            @endif

            <!-- Action card checks before voting opens -->
            @if($currentAgenda?->status === 'voting' && $currentAgenda->reveal_step)
                @php($steps = $currentAgenda->revealSteps())
                @php($stepIndex = (int) array_search($currentAgenda->reveal_step, $steps))
                @php($nextStep = $currentAgenda->nextRevealStep())
                @php($isArgentStep = $currentAgenda->reveal_step === \App\Models\Agenda::STEP_ARGENT_VOTES)
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex flex-1 gap-1.5" aria-hidden="true">
                            @foreach($steps as $index => $step)
                                <span class="h-1.5 flex-1 rounded-full {{ $index <= $stepIndex ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700' }}"></span>
                            @endforeach
                        </div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            Step {{ $stepIndex + 1 }} of {{ count($steps) }}
                        </p>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        @if($isArgentStep)
                            The Argent Flight votes first
                        @else
                            &ldquo;{{ $currentAgenda->reveal_step_label }}&rdquo; cards
                        @endif
                    </h3>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">
                        @if($currentAgenda->reveal_step === \App\Models\Agenda::STEP_WHEN_REVEALED)
                            Ask the players if anyone wants to play a card with &ldquo;When an agenda is revealed&rdquo;, such as Veto. Resolve those cards before continuing. If the agenda is discarded, use Discard Agenda and reveal the next one.
                        @elseif($isArgentStep)
                            {{ $game->argentFlightPlayerName() ?? 'The Argent Flight player' }} votes before everyone else and announces their votes publicly. Ask them to say which outcome they vote for and how many votes they cast. They still enter their vote in the app once voting opens.
                        @else
                            Ask the players if anyone wants to play a card with &ldquo;After an agenda is revealed&rdquo;, such as a Rider or Assassinate Representative. Resolve those cards before continuing.
                        @endif
                    </p>
                    <button wire:click="completeRevealStep"
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                        @if($nextStep === \App\Models\Agenda::STEP_AFTER_REVEALED)
                            {{ 'Done, continue to "After" cards' }}
                        @elseif($nextStep === \App\Models\Agenda::STEP_ARGENT_VOTES)
                            Done, continue to The Argent Flight
                        @else
                            Done, start voting
                        @endif
                    </button>
                </div>
            @endif

            <!-- Create Agenda Form -->
            @if($showCreateAgenda && $currentAgenda?->status !== 'voting')
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Create New Agenda</h3>

                    <form wire:submit="createAgenda" class="space-y-5">
                        <!-- Preset Agenda Selection -->
                        <div>
                            <label for="selectedPresetAgenda" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Preset agenda <span class="font-normal text-gray-500 dark:text-gray-400">(optional)</span>
                            </label>
                            <div class="flex gap-2">
                                <select id="selectedPresetAgenda"
                                        wire:model.live="selectedPresetAgenda"
                                        class="{{ $inputClass }} min-w-0 flex-1">
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
                                            class="shrink-0 px-4 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                                        Clear
                                    </button>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Pick a preset to fill in the form, or create a custom one from scratch.
                            </p>
                        </div>

                        <div>
                            <label for="newAgendaTitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Agenda Title
                            </label>
                            <input type="text"
                                   id="newAgendaTitle"
                                   wire:model="newAgendaTitle"
                                   placeholder="e.g., Elect New Speaker, Trade Agreement"
                                   class="{{ $inputClass }}">
                            @error('newAgendaTitle') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="newAgendaDescription" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Description
                            </label>
                            <textarea id="newAgendaDescription"
                                      wire:model="newAgendaDescription"
                                      rows="3"
                                      placeholder="Describe what this agenda is about..."
                                      class="{{ $inputClass }}"></textarea>
                            @error('newAgendaDescription') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <fieldset>
                            <legend class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Agenda Type
                            </legend>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach(['law' => 'Law', 'directive' => 'Directive'] as $value => $label)
                                    <label class="flex items-center justify-center px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer text-gray-900 dark:text-white has-checked:border-blue-600 has-checked:bg-blue-50 dark:has-checked:bg-blue-900/30 has-checked:font-semibold has-focus-visible:ring-2 has-focus-visible:ring-blue-500">
                                        <input type="radio"
                                               wire:model="newAgendaCardType"
                                               value="{{ $value }}"
                                               class="sr-only">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('newAgendaCardType') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </fieldset>

                        <fieldset>
                            <legend class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Voting Type
                            </legend>
                            <div class="space-y-2">
                                @foreach($votingTypes as $value => $type)
                                    @php($disabled = $type['planet'] && ! $hasMilty)
                                    <label class="flex items-center gap-3 px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg has-checked:border-blue-600 has-checked:bg-blue-50 dark:has-checked:bg-blue-900/30 {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
                                        <input type="radio"
                                               wire:model.live="agendaType"
                                               value="{{ $value }}"
                                               @disabled($disabled)
                                               class="h-5 w-5 shrink-0 text-blue-600">
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $type['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('agendaType') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                            @unless($hasMilty)
                                <p class="mt-2 text-xs text-red-600 dark:text-red-400">
                                    Not all options are available as this game was not created with Milty's Draft Tool. To use all agenda types, create a new game using the draft tool and import your players and factions from this game.
                                </p>
                            @endunless
                        </fieldset>

                        @if($votingTypes[$agendaType]['hint'] ?? null)
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    {{ $votingTypes[$agendaType]['hint'] }}
                                </p>
                            </div>
                        @endif

                        @if($agendaType === 'custom')
                            <div>
                                <label for="customOptions" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Custom Options (comma separated)
                                </label>
                                <input type="text"
                                       id="customOptions"
                                       wire:model="customOptions"
                                       placeholder="Option 1, Option 2, Option 3"
                                       class="{{ $inputClass }}">
                                @error('customOptions') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        @if(str_starts_with($agendaType, 'elect_planet_'))
                            <div>
                                <label for="customTileNumbers" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Additional Tile Numbers <span class="font-normal text-gray-500 dark:text-gray-400">(optional)</span>
                                </label>
                                <input type="text"
                                       id="customTileNumbers"
                                       wire:model="customTileNumbers"
                                       placeholder="18, 25, 32"
                                       class="{{ $inputClass }}">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Comma separated. Adds the planets on these tiles to the voting options.
                                </p>
                            </div>
                        @endif

                        <div class="flex flex-col gap-2 pt-1 sm:flex-row">
                            <button type="submit"
                                    class="w-full sm:w-auto bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                                Create Agenda
                            </button>
                            <button type="button"
                                    wire:click="toggleCreateAgenda"
                                    class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
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
            @php($canBlockVotes = $player->is_speaker && $currentAgenda?->status === 'voting' && $currentAgenda->reveal_step)
            @foreach($players as $gamePlayer)
                @php($isBlocked = $currentAgenda?->status === 'voting' && $currentAgenda->isPlayerBlocked($gamePlayer))
                <div class="flex flex-col gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 font-semibold text-gray-900 dark:text-white">
                            <span class="truncate">{{ $gamePlayer->name }}</span>
                            @if($gamePlayer->is_speaker)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    Speaker
                                </span>
                            @endif
                            @if($isBlocked)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    Cannot vote
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

                    @if($player->is_speaker)
                        <div class="flex flex-wrap gap-2">
                            @if($canBlockVotes)
                                <button wire:click="toggleVotingBlock('{{ $gamePlayer->id }}')"
                                        class="text-xs font-medium px-3 py-2 rounded-md transition-colors {{ $isBlocked ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-orange-500 hover:bg-orange-600 text-white' }}">
                                    {{ $isBlocked ? 'Allow vote' : 'Block vote' }}
                                </button>
                            @endif
                            @if($gamePlayer->id !== $player->id)
                                <button wire:click="transferSpeaker('{{ $gamePlayer->id }}')"
                                        wire:confirm="Are you sure you want to transfer the Speaker token to {{ $gamePlayer->name }}?"
                                        class="text-xs font-medium bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-md transition-colors">
                                    Make Speaker
                                </button>
                                <button wire:click="removePlayer('{{ $gamePlayer->id }}')"
                                        wire:confirm="Remove {{ $gamePlayer->name }} from the game? Their votes are deleted too."
                                        class="text-xs font-medium bg-gray-200 hover:bg-red-600 hover:text-white dark:bg-gray-600 text-gray-800 dark:text-gray-100 px-3 py-2 rounded-md transition-colors">
                                    Remove
                                </button>
                            @endif
                        </div>
                        <x-rejoin-link :player="$gamePlayer" />
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Current Agenda -->
    @if($currentAgenda)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
          <div class="flex flex-col md:flex-row gap-6">
            <div class="flex flex-col items-center gap-3 md:w-80 shrink-0">
                <x-agenda-card :agenda="$currentAgenda" />
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    @if($currentAgenda->reveal_step) bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                    @elseif($currentAgenda->status === 'voting') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 @endif">
                    {{ $currentAgenda->reveal_step_label ?? ucfirst($currentAgenda->status) }}
                </span>
            </div>

            <div class="flex-1 min-w-0">

            @if($currentAgenda->reveal_step)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <p class="text-yellow-800 dark:text-yellow-200 font-medium">Voting has not started yet</p>
                    <p class="text-sm text-yellow-800 dark:text-yellow-200 mt-1">
                        @if($currentAgenda->reveal_step === \App\Models\Agenda::STEP_ARGENT_VOTES)
                            @if(\App\Models\Game::isArgentFlight($player->faction))
                                You play The Argent Flight, so you vote first: announce your votes to everyone. Voting opens when the Speaker is done.
                            @else
                                The Argent Flight votes first and announces their votes. Voting opens when the Speaker is done.
                            @endif
                        @else
                            The Speaker is asking for &ldquo;{{ $currentAgenda->reveal_step_label }}&rdquo; action cards. Voting opens when the Speaker is done.
                        @endif
                    </p>
                </div>
            @elseif($currentAgenda->status === 'voting' && $currentAgenda->isPlayerBlocked($player))
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <p class="text-red-800 dark:text-red-200 font-medium">You cannot vote on this agenda</p>
                </div>
            @elseif($currentAgenda->isVotingOpen() && !$hasVoted)
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
            @php($voteCount = $currentAgenda->votes()->count())
            @php($eligibleCount = $currentAgenda->eligiblePlayerCount())
            <div class="border-t pt-4 mt-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Voting Progress</span>
                    <span class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $voteCount }} / {{ $eligibleCount }} players voted
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full"
                         style="width: {{ $eligibleCount > 0 ? ($voteCount / $eligibleCount) * 100 : 0 }}%"></div>
                </div>
            </div>

            <!-- Results (Speaker Only or when voting ended) -->
            @if($speakerViewResults || $currentAgenda->status === 'completed')
                <div class="border-t pt-4 mt-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Voting Results</h3>
                    @if(collect($voteResults)->sum('influence') == 0)
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-4">All players abstained or no influence was spent on votes. No results to display.</h4>
                    @endif
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
          </div>
        </div>
    @endif
</div>
