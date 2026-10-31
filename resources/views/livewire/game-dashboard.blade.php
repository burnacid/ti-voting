<div class="space-y-6">
    {{-- For the tabs. Keep this block above the one-line php directives, Blade mixes them up otherwise --}}
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
        // Outline icons for the tabs (24x24, stroked)
        $tabIcons = [
            'speaker' => ['M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z', 'M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
            'players' => ['M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
            'star-chart' => ['M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z'],
        ];
    @endphp

    <!-- Requests to rejoin as a player, any other player confirms them -->
    @foreach($rejoinRequests as $requested)
        <div wire:key="rejoin-{{ $requested->id }}" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <p class="font-semibold text-blue-900 dark:text-blue-100">Someone wants to rejoin as {{ $requested->name }}</p>
            <p class="text-sm text-blue-800 dark:text-blue-200 mt-1">
                Only allow this if {{ $requested->name }} is at the table and lost access. They will play as {{ $requested->name }}{{ $requested->is_speaker ? ', including the Speaker controls' : '' }}.
            </p>
            <div class="mt-3 flex flex-col sm:flex-row gap-2">
                <button wire:click="approveRejoin('{{ $requested->id }}')"
                        class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                    Yes, it is {{ $requested->name }}
                </button>
                <button wire:click="declineRejoin('{{ $requested->id }}')"
                        class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                    Decline
                </button>
            </div>
        </div>
    @endforeach

    <!-- Managed influence: the Speaker started the agenda phase, every player enters their influence -->
    {{-- A modal that cannot be closed: it stays until the player saved their influence --}}
    @if($needsPhaseInfluence)
        <div wire:key="phase-influence-modal" class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50">
            <form wire:submit="savePhaseInfluence" role="dialog" aria-modal="true"
                  aria-labelledby="phase-influence-title" aria-describedby="phase-influence-description"
                  x-data x-trap.noscroll="true"
                  class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                <h2 id="phase-influence-title" class="text-xl font-bold text-gray-900 dark:text-white mb-2">The agenda phase has started</h2>
                <p id="phase-influence-description" class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                    Enter the influence you have available. Your votes are taken from it.
                </p>

                <label for="phaseInfluence" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Your available influence</label>
                <div class="flex gap-2">
                    <input type="number" id="phaseInfluence" wire:model="phaseInfluence" min="0" max="99" inputmode="numeric" placeholder="0" autofocus
                           class="w-24 px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    <button type="submit"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                        Save
                    </button>
                </div>
                @error('phaseInfluence') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
            </form>
        </div>
    @endif

    <!-- Pick a drafted faction, e.g. after the Speaker added the Milty draft -->
    @if($factionChoices->isNotEmpty())
        <form wire:submit="chooseFaction" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <label for="chosenFaction" class="block font-semibold text-blue-900 dark:text-blue-100">Choose your faction</label>
            <p class="text-sm text-blue-800 dark:text-blue-200 mt-1">This game has a draft or map. Pick your faction.</p>
            <div class="mt-3 flex flex-col sm:flex-row gap-2">
                <select id="chosenFaction" wire:model="chosenFaction"
                        class="w-full sm:flex-1 px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    <option value="">Choose the faction you drafted</option>
                    @foreach($factionChoices as $factionName => $drafter)
                        <option value="{{ $factionName }}">{{ $factionName }}{{ $drafter ? " ({$drafter})" : '' }}</option>
                    @endforeach
                </select>
                <button type="submit"
                        class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                    Save
                </button>
            </div>
            @error('chosenFaction') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </form>
    @endif

    @if($player->hasPendingRejoinRequest())
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
            <p class="font-semibold text-yellow-900 dark:text-yellow-100">Someone is asking to rejoin as you</p>
            <p class="text-sm text-yellow-800 dark:text-yellow-200 mt-1">
                If that was not you on another device, ask the other players to decline it.
            </p>
        </div>
    @endif

    <!-- Laws in effect, shown to every player -->
    @if($game->representative_government)
        <div class="flex items-start gap-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
            <span class="shrink-0 mt-0.5 px-2 py-0.5 rounded text-xs font-semibold uppercase bg-amber-200 text-amber-900 dark:bg-amber-800 dark:text-amber-100">Law</span>
            <div>
                <p class="font-semibold text-amber-900 dark:text-amber-100">Representative Government is in effect</p>
                <p class="text-sm text-amber-800 dark:text-amber-200">
                    @php($mecatolRex = $game->mecatolRexPlayer?->name)
                    Players cannot exhaust planets to vote. Each player casts 1 vote on each agenda{{ $game->absol_agendas
                        ? ', except the player who controls Mecatol Rex' . ($mecatolRex ? " ({$mecatolRex})" : '') . ', who may cast 2. Players cannot cast additional votes.'
                        : '.' }}
                </p>
            </div>
        </div>
    @endif
    @if($game->termLimitsInEffect())
        <div class="flex items-start gap-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
            <span class="shrink-0 mt-0.5 px-2 py-0.5 rounded text-xs font-semibold uppercase bg-amber-200 text-amber-900 dark:bg-amber-800 dark:text-amber-100">Law</span>
            <div>
                <p class="font-semibold text-amber-900 dark:text-amber-100">Term Limits is in effect</p>
                <p class="text-sm text-amber-800 dark:text-amber-200">
                    When an agenda resolves the way the Speaker voted or predicted, or the Speaker broke the tie, the other player who cast the most votes for an outcome that was not resolved takes the speaker token.
                </p>
            </div>
        </div>
    @endif

    <!-- Speaker: reveal the next agenda, shown above the tabs -->
    @if($player->is_speaker && $currentAgenda?->status !== 'voting')
        @if($showCreateAgenda)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 sm:p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Create New Agenda</h3>

                <form wire:submit="createAgenda" class="space-y-5">
                    <!-- Preset Agenda Selection -->
                    <div>
                        <label for="selectedPresetAgenda" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Preset agenda <span class="font-normal text-gray-500 dark:text-gray-400">(optional)</span>
                        </label>
                        <div class="flex gap-2">
                            {{-- Keyed on the agenda deck, so the list is rebuilt when the Speaker switches decks --}}
                            <x-agenda-search :agendas="$availableAgendas" :input-class="$inputClass" wire:key="agenda-search-{{ $game->absol_agendas ? 'absol' : 'official' }}" />
                            @if($selectedPresetAgenda)
                                <button type="button"
                                        wire:click="clearPresetSelection"
                                        class="shrink-0 px-4 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                                    Clear
                                </button>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Search by name or effect to fill in the form, or leave it empty to create a custom agenda.
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
                                Not all options are available as this game has no map. To use all agenda types, the Speaker can add a Milty or TI4 Lab draft URL, or a map string, with Change Map.
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
        @else
            <div class="flex flex-col sm:flex-row gap-2">
                @if($game->managed_influence)
                    {{-- Confirmed in a modal, as it clears every player's influence --}}
                    <div x-data="{ confirming: false }" x-on:keydown.escape.window="confirming = false" class="contents">
                        <button type="button" x-on:click="confirming = true"
                                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                            Start New Agenda Phase
                        </button>

                        <template x-teleport="body">
                            <div x-show="confirming" x-cloak x-transition.opacity
                                 class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50"
                                 x-on:click.self="confirming = false">
                                <div role="dialog" aria-modal="true" aria-labelledby="start-phase-title" aria-describedby="start-phase-description"
                                     x-trap.noscroll="confirming"
                                     class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                                    <h2 id="start-phase-title" class="text-xl font-bold text-gray-900 dark:text-white mb-2">Start New Agenda Phase</h2>
                                    <p id="start-phase-description" class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                                        Every player's influence is cleared, and every player is asked to enter the influence they have for this agenda phase.
                                    </p>
                                    <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
                                        <button type="button" x-on:click="confirming = false"
                                                class="px-4 py-2.5 sm:py-2 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                                            Cancel
                                        </button>
                                        <button type="button" x-on:click="confirming = false; $wire.startAgendaPhase()"
                                                class="px-4 py-2.5 sm:py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700">
                                            Start New Agenda Phase
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                @endif
                <button wire:click="toggleCreateAgenda"
                        class="w-full sm:w-auto bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                    Create New Agenda
                </button>
            </div>
        @endif
        @if($missingInfluence->isNotEmpty())
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Waiting for influence from {{ $missingInfluence->pluck('name')->sort()->implode(', ') }}.
            </p>
        @endif
    @endif

    <!-- A correct prediction whose reward depends on who voted for the outcome, e.g. The Nekro Virus. Only this player sees it -->
    @foreach($correctVoterPredictions as ['prediction' => $prediction, 'voters' => $voters])
        <div wire:key="voter-prediction-{{ $prediction->id }}" class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl p-4">
            <p class="font-semibold text-purple-900 dark:text-purple-100">Your prediction came true: {{ $prediction->option }} won</p>
            <p class="text-sm text-purple-800 dark:text-purple-200 mt-1">
                <strong>{{ $prediction->source_label }}:</strong> {{ $prediction->reward }}
            </p>
            @if($voters->isEmpty())
                <p class="text-sm text-gray-700 dark:text-gray-300 mt-3">Nobody voted {{ $prediction->option }}, so there is no player to take a technology from.</p>
            @else
                <p class="text-sm font-medium text-gray-900 dark:text-white mt-3">Players who voted {{ $prediction->option }}</p>
                <ul role="list" class="mt-1 flex flex-wrap gap-2">
                    @foreach($voters as $voter)
                        <li class="px-2.5 py-1 rounded-md text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-purple-200 dark:border-purple-800">
                            <span class="font-medium">{{ $voter->name }}</span>
                            @if($voter->faction)
                                <span class="text-gray-600 dark:text-gray-400">· {{ $voter->faction }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach

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

            @if($currentAgenda->reveal_step && $player->is_speaker)
                <!-- Action card checks before voting opens -->
                @php($steps = $currentAgenda->revealSteps())
                @php($stepIndex = (int) array_search($currentAgenda->reveal_step, $steps))
                @php($nextStep = $currentAgenda->nextRevealStep())
                @php($isArgentStep = $currentAgenda->reveal_step === \App\Models\Agenda::STEP_ARGENT_VOTES)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex flex-1 gap-1.5" aria-hidden="true">
                            @foreach($steps as $index => $step)
                                <span class="h-1.5 flex-1 rounded-full {{ $index <= $stepIndex ? 'bg-blue-600' : 'bg-yellow-200 dark:bg-yellow-800' }}"></span>
                            @endforeach
                        </div>
                        <p class="text-xs font-medium text-yellow-800 dark:text-yellow-200 whitespace-nowrap">
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
                            Ask the players if anyone wants to play a card with &ldquo;After an agenda is revealed&rdquo;, such as a Rider or Assassinate Representative. Resolve those cards before continuing. Record Riders and other predictions below, so everyone sees them. To stop a player from voting, use Block vote in the Players tab.
                        @endif
                    </p>
                    <div class="flex flex-col sm:flex-row gap-2">
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
                        <button wire:click="discardAgenda"
                                wire:confirm="Discard this agenda, for example because it was vetoed? You can then reveal the next agenda."
                                class="w-full sm:w-auto bg-gray-600 hover:bg-gray-700 active:bg-gray-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                            Discard Agenda
                        </button>
                    </div>
                </div>
            @elseif($currentAgenda->reveal_step)
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
                    @if($predictions->contains('player_id', $player->id))
                        <p class="text-sm text-red-800 dark:text-red-200 mt-1">You predicted an outcome.</p>
                    @endif
                </div>
            @elseif($currentAgenda->isVotingOpen() && !$hasVoted)
                <!-- Voting Form -->
                <form wire:submit="submitVote" class="space-y-4">
                    @if(!$currentAgenda->secret_votes)
                        <p class="text-sm text-blue-800 dark:text-blue-200 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                            Hidden Agenda is off: every player sees how you vote.
                        </p>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Choose your option:
                        </label>
                        @if($noInfluence)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                {{ $onlyAbstain ? 'You have no influence left, so you abstain.' : 'You have 0 influence, so Abstain is selected.' }}
                            </p>
                        @endif
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            @foreach($currentAgenda->options as $option)
                                @php($optionDisabled = $onlyAbstain && $option !== 'Abstain')
                                <label class="flex items-center p-2 rounded-md
                                    {{ $option === 'Abstain' ? 'bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 border border-red-200 dark:border-red-800' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}
                                    {{ $optionDisabled ? 'opacity-50 cursor-not-allowed' : '' }}
                                    transition-colors">
                                    <input type="radio"
                                           wire:model.live="selectedOption"
                                           @disabled($optionDisabled)
                                           value="{{ $option }}"
                                           class="{{ $option === 'Abstain' ? 'text-red-600' : 'text-blue-600' }} mr-2">
                                    <span class="{{ $option === 'Abstain' ? 'text-red-800 dark:text-red-200' : 'text-gray-900 dark:text-white' }}">{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedOption') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    @if($game->representative_government)
                        @php($myVotes = $game->representativeVotesFor($player))
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                            @if($myVotes > 1 && $selectedOption !== 'Abstain')
                                {{-- Absol's Representative Government: the player who controls Mecatol Rex may cast 2 votes --}}
                                <fieldset>
                                    <legend class="text-sm text-blue-800 dark:text-blue-200">
                                        Representative Government is in effect. You control Mecatol Rex, so you may cast up to {{ $myVotes }} votes.
                                    </legend>
                                    <div class="mt-2 flex gap-2">
                                        @foreach(range(1, $myVotes) as $count)
                                            <label class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2.5 sm:py-2 rounded-lg border cursor-pointer transition-colors
                                                {{ (int) $influenceSpent === $count ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white dark:bg-gray-800 border-blue-200 dark:border-blue-800 text-gray-900 dark:text-white hover:bg-blue-100 dark:hover:bg-blue-900/40' }}">
                                                <input type="radio" wire:model.live="influenceSpent" value="{{ $count }}" class="sr-only">
                                                <span class="font-semibold">{{ $count }} {{ $count === 1 ? 'vote' : 'votes' }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                                @error('influenceSpent') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @else
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    Representative Government is in effect: your vote counts as 1 vote.
                                </p>
                            @endif
                        </div>
                    @elseif($selectedOption !== 'Abstain')
                    <div>
                        @php($managedMax = $game->managed_influence && $player->influence !== null ? min(99, $player->influence) : null)
                        <label for="influenceSpent" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Influence to spend (1-{{ $managedMax ?? 99 }}):
                        </label>
                        @if($player->influence !== null)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                You have {{ $player->influence }} influence available.{{ $managedMax !== null ? ' It goes down by the influence you spend.' : '' }}
                            </p>
                        @endif
                        <input type="number"
                               id="influenceSpent"
                               wire:model="influenceSpent"
                               min="1"
                               max="{{ $managedMax ?? 99 }}"
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

            @php($canPredict = $currentAgenda->status === 'voting' && $currentAgenda->reveal_step !== null)
            @if($canPredict || $predictions->isNotEmpty())
                <x-agenda-predictions :agenda="$currentAgenda" :predictions="$predictions" :player="$player" :players="$players"
                                      :can-predict="$canPredict"
                                      :outcome="$currentAgenda->status === 'completed' ? $currentAgenda->outcome() : null" />
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
                                    @if(!$currentAgenda->secret_votes)
                                        {{-- Without Hidden Agenda: who voted for this option, with the influence they spent --}}
                                        <ul class="mt-2 flex flex-wrap gap-2">
                                            @foreach($openVotes->where('option', $option)->sortByDesc('influence_spent') as $vote)
                                                @php($voter = $players->firstWhere('id', $vote->player_id))
                                                @if($voter)
                                                    <li class="flex items-center gap-1.5 pl-2.5 pr-0.5 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $voter->name }}
                                                        <x-influence-hex :value="$vote->influence_spent" small title="{{ $vote->influence_spent }} influence spent" sr-label="influence spent" />
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Outcomes like 'Each player who voted "For"': the Speaker shows who they apply to. Predictions also need the winner -->
                    @php($affectsVoters = $currentAgenda->affectsVoters())
                    @if($player->is_speaker && $currentAgenda->status === 'completed' && ($affectsVoters || $predictions->isNotEmpty() || $game->termLimitsInEffect()))
                        @php($outcome = $currentAgenda->outcome())
                        <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            @if($outcome === null)
                                <p class="font-semibold text-blue-900 dark:text-blue-100">The vote is tied</p>
                                <p class="text-sm text-blue-800 dark:text-blue-200 mt-1">
                                    @if($affectsVoters)
                                        This agenda affects players by how they voted. As Speaker you break the tie first, then you can see who is affected.
                                    @elseif($predictions->isEmpty())
                                        As Speaker you break the tie. Under Term Limits that can move the speaker token.
                                    @else
                                        As Speaker you break the tie first, then you can see which predictions are correct.
                                    @endif
                                </p>
                                <div class="mt-3 flex flex-col sm:flex-row gap-2">
                                    @foreach($currentAgenda->leadingOptions() as $option)
                                        <button wire:click="breakTie(@js($option))"
                                                wire:confirm="Break the tie in favour of {{ $option }}?"
                                                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                                            {{ $option }} wins
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="font-semibold text-blue-900 dark:text-blue-100">
                                        {{ $outcome }} wins{{ $currentAgenda->tiebreak_option ? ' (tie broken by the Speaker)' : '' }}
                                    </p>
                                    @if($affectsVoters)
                                        <button wire:click="toggleAffectedPlayers"
                                                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                                            {{ $showAffectedPlayers ? 'Hide who is affected' : 'Show who is affected' }}
                                        </button>
                                    @endif
                                </div>

                                @if($affectsVoters && $showAffectedPlayers)
                                    @php($effect = $currentAgenda->outcomeEffect($outcome))
                                    @php($affected = $currentAgenda->playersAffectedBy($outcome))
                                    @php($topVoters = $currentAgenda->topVotersBy($outcome))
                                    @php($orderedVoters = $currentAgenda->votersInOrderBy($outcome))
                                    {{-- An ordered list replaces the plain list of the same voters, e.g. Seed of an Empire --}}
                                    @php($affected = array_diff_key($affected, array_flip(array_column($orderedVoters, 'option'))))
                                    <div class="mt-3 space-y-3">
                                        @if($effect)
                                            <p class="text-sm text-blue-800 dark:text-blue-200"><strong>{{ $outcome }}:</strong> {{ $effect }}</p>
                                        @endif
                                        <!-- Outcomes like 'The player who cast the most votes "For"' -->
                                        @foreach($topVoters as $top)
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    @if(in_array($top['option'], ['For', 'Against'], true))
                                                        {{ $top['other'] ? 'Other player' : 'Player' }} who cast the most votes &ldquo;{{ $top['option'] }}&rdquo;
                                                    @else
                                                        {{ $top['other'] ? 'Other player' : 'Player' }} who cast the most votes for {{ $top['option'] }}
                                                    @endif
                                                </p>
                                                @if(empty($top['players']))
                                                    <p class="text-sm text-gray-600 dark:text-gray-300">Nobody, so this part affects no one.</p>
                                                @else
                                                    @if(count($top['players']) > 1)
                                                        <p class="text-sm text-gray-600 dark:text-gray-300">Tied with {{ $top['influence'] }} votes each.</p>
                                                    @endif
                                                    <ul class="mt-1 flex flex-wrap gap-2">
                                                        @foreach($top['players'] as $name)
                                                            <li class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-sm font-medium bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-blue-200 dark:border-blue-800">
                                                                {{ $name }}
                                                                <x-influence-hex :value="$top['influence']" small title="{{ $top['influence'] }} votes" sr-label="votes" />
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        @endforeach
                                        <!-- Outcomes like 'each other player that voted for them ... (from most votes to least)' -->
                                        @foreach($orderedVoters as $ordered)
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    @if(in_array($ordered['option'], ['For', 'Against'], true))
                                                        {{ $ordered['other'] ? 'Other players' : 'Players' }} who voted &ldquo;{{ $ordered['option'] }}&rdquo;, from most votes to least
                                                    @else
                                                        {{ $ordered['other'] ? 'Other players' : 'Players' }} who voted for {{ $ordered['option'] }}, from most votes to least
                                                    @endif
                                                </p>
                                                @if(empty($ordered['voters']))
                                                    <p class="text-sm text-gray-600 dark:text-gray-300">Nobody, so this part affects no one.</p>
                                                @else
                                                    <ol class="mt-1 flex flex-wrap gap-2">
                                                        @foreach($ordered['voters'] as $voter)
                                                            <li class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-sm font-medium bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-blue-200 dark:border-blue-800">
                                                                <span class="text-gray-500 dark:text-gray-400">{{ $loop->iteration }}.</span>
                                                                {{ $voter['name'] }}
                                                                <x-influence-hex :value="$voter['influence']" small title="{{ $voter['influence'] }} votes" sr-label="votes" />
                                                            </li>
                                                        @endforeach
                                                    </ol>
                                                @endif
                                            </div>
                                        @endforeach
                                        @forelse($affected as $vote => $names)
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">Players who voted &ldquo;{{ $vote }}&rdquo;</p>
                                                @if(empty($names))
                                                    <p class="text-sm text-gray-600 dark:text-gray-300">Nobody, so this outcome affects no one.</p>
                                                @else
                                                    <ul class="mt-1 flex flex-wrap gap-2">
                                                        @foreach($names as $name)
                                                            <li class="px-2.5 py-1 rounded-md text-sm font-medium bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-blue-200 dark:border-blue-800">{{ $name }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        @empty
                                            @if(empty($topVoters) && empty($orderedVoters))
                                                <p class="text-sm text-gray-600 dark:text-gray-300">This outcome does not depend on how players voted.</p>
                                            @endif
                                        @endforelse
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif

                    <!-- Term Limits: the speaker token may move. Only for the Speaker of this agenda, while they still have the token -->
                    @php($handover = $player->is_speaker && $player->id === $currentAgenda->speaker_id ? $currentAgenda->termLimitsHandover() : null)
                    @if($handover)
                        @php($reasonLabels = ['voted' => 'you voted for it', 'predicted' => 'you predicted it', 'tiebreak' => 'you broke the tie'])
                        <div class="mt-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                            <p class="font-semibold text-amber-900 dark:text-amber-100">Term Limits: the speaker token moves</p>
                            <p class="text-sm text-amber-800 dark:text-amber-200 mt-1">
                                {{ $currentAgenda->outcome() }} was resolved and {{ collect($handover['reasons'])->map(fn ($reason) => $reasonLabels[$reason])->join(', ', ' and ') }}.
                                The other player who cast the most votes for an outcome that was not resolved takes the speaker token.
                            </p>
                            @if(empty($handover['candidates']))
                                <p class="text-sm text-amber-800 dark:text-amber-200 mt-2">Nobody else voted for an outcome that was not resolved, so you keep the speaker token.</p>
                            @else
                                @if(count($handover['candidates']) > 1)
                                    <p class="text-sm text-amber-800 dark:text-amber-200 mt-2">Tied with {{ $handover['candidates'][0]['influence'] }} votes each. Agree with the table who takes it.</p>
                                @endif
                                <div class="mt-3 flex flex-col sm:flex-row sm:flex-wrap gap-2">
                                    @foreach($handover['candidates'] as $candidate)
                                        <button wire:click="transferSpeaker('{{ $candidate['player']->id }}')"
                                                wire:confirm="Give the speaker token to {{ $candidate['player']->name }}?"
                                                class="w-full sm:w-auto bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                                            Give speaker token to {{ $candidate['player']->name }}
                                            <span class="font-normal opacity-90">({{ $candidate['influence'] }} votes for {{ $candidate['option'] }})</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
            </div>
          </div>
        </div>
    @endif

    <!-- Players, Star Chart and Speaker Controls, one at a time -->
    @php($tabs = array_filter(['players' => 'Players', 'star-chart' => $game->milty_draft_data ? 'Star Chart' : null, 'speaker' => $player->is_speaker ? 'Speaker Settings' : null]))
    @php($defaultTab = 'players')
    {{-- Keyed on the tabs, so a new Speaker or a map added later starts on a tab that exists --}}
    <div wire:key="tabs-{{ implode('-', array_keys($tabs)) }}"
         x-data="{
            tabs: @js(array_keys($tabs)),
            tab: @js($defaultTab),
            storageKey: @js('game-tab-' . $game->code),
            init() {
                let saved = null;
                try { saved = localStorage.getItem(this.storageKey); } catch (e) {}
                if (this.tabs.includes(saved)) this.tab = saved;
                this.$watch('tab', (value) => { try { localStorage.setItem(this.storageKey, value); } catch (e) {} });
            },
         }"
         class="space-y-4">
        @if(count($tabs) > 1)
            <div role="tablist" aria-label="Game sections" class="flex gap-1 p-1 bg-gray-200/70 dark:bg-gray-800 rounded-xl">
                @foreach($tabs as $key => $label)
                    <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="tab-panel-{{ $key }}"
                            x-on:click="tab = '{{ $key }}'"
                            x-bind:aria-selected="tab === '{{ $key }}'"
                            x-bind:class="tab === '{{ $key }}' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                            class="flex-1 min-w-0 flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 px-2 sm:px-3 py-2 rounded-lg text-xs sm:text-sm font-semibold leading-tight transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            @foreach($tabIcons[$key] as $path)
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"></path>
                            @endforeach
                        </svg>
                        <span class="text-center">
                            {{ $label }}
                            @if($key === 'players')
                                <span class="ml-0.5 font-medium text-gray-500 dark:text-gray-400">{{ $players->count() }}</span>
                            @endif
                        </span>
                    </button>
                @endforeach
            </div>
        @endif

        <div id="tab-panel-players" role="tabpanel" aria-labelledby="tab-players"
             {{ $defaultTab !== 'players' ? 'x-cloak' : '' }}
             x-show="tab === 'players'">
            <!-- Players List -->
            @php($canBlockVotes = $player->is_speaker && $currentAgenda?->status === 'voting' && $currentAgenda->reveal_step)
            @php($votingOpen = $currentAgenda?->isVotingOpen() ?? false)
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Players</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @if($votingOpen)
                                {{ $currentAgenda->votes()->count() }} of {{ $currentAgenda->eligiblePlayerCount() }} voted
                            @elseif($canBlockVotes)
                                Block players who cannot vote on this agenda.
                            @else
                                {{ $players->count() }} {{ Str::plural('player', $players->count()) }} in this game
                            @endif
                        </p>
                    </div>
                    <button wire:click="refreshData" title="Refresh" aria-label="Refresh"
                            class="shrink-0 p-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors">
                        <svg class="w-5 h-5" wire:loading.class="animate-spin" wire:target="refreshData" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"></path>
                        </svg>
                    </button>
                </div>


                <ul role="list" class="grid grid-cols-1 gap-2 md:grid-cols-2">
                    @foreach($players as $gamePlayer)
                        @php($hasPredicted = $currentAgenda?->status === 'voting' && $predictions->contains('player_id', $gamePlayer->id))
                        @php($isBlocked = $currentAgenda?->status === 'voting' && $currentAgenda->isPlayerBlocked($gamePlayer))
                        @php($hasVotedHere = $currentAgenda && $gamePlayer->hasVotedOn($currentAgenda))
                        @php($isMe = $gamePlayer->id === $player->id)
                        <li wire:key="player-{{ $gamePlayer->id }}"
                            class="min-w-0 flex items-center gap-3 p-3 rounded-lg border {{ $isMe ? 'border-blue-200 bg-blue-50/60 dark:border-blue-900 dark:bg-blue-950/30' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/30' }}">
                            <x-player-avatar :player="$gamePlayer" />

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="font-semibold text-gray-900 dark:text-white truncate">{{ $gamePlayer->name }}</span>
                                    @if($gamePlayer->is_speaker)
                                        <span class="shrink-0 px-1.5 py-0.5 rounded text-[11px] font-semibold uppercase tracking-wide bg-yellow-100 text-yellow-800 dark:bg-yellow-900/60 dark:text-yellow-200">Speaker</span>
                                    @endif
                                    @if($isMe)
                                        <span class="shrink-0 px-1.5 py-0.5 rounded text-[11px] font-semibold uppercase tracking-wide bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200">You</span>
                                    @endif
                                </div>
                                @if($gamePlayer->faction)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $gamePlayer->faction }}</p>
                                @endif
                                @if($canBlockVotes && $isBlocked && !$hasPredicted)
                                    <p class="text-xs font-semibold text-red-700 dark:text-red-400">Cannot vote</p>
                                @endif
                            </div>

                            <!-- Available influence, as the blue influence value on a TI4 planet card -->
                            @if($gamePlayer->influence !== null)
                                <x-influence-hex :value="$gamePlayer->influence" />
                            @endif

                            <!-- Vote status, or blocking votes while the Speaker checks action cards. Players who predicted cannot vote -->
                            @if($hasPredicted)
                                <span class="shrink-0 px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-200">Predicted</span>
                            @elseif($canBlockVotes)
                                <button wire:click="toggleVotingBlock('{{ $gamePlayer->id }}')"
                                        class="shrink-0 text-xs font-semibold px-3 py-2 rounded-lg transition-colors {{ $isBlocked ? 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-200' : 'bg-orange-500 hover:bg-orange-600 text-white' }}">
                                    {{ $isBlocked ? 'Allow vote' : 'Block vote' }}
                                </button>
                            @elseif($isBlocked)
                                <span class="shrink-0 px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200">Cannot vote</span>
                            @elseif($hasVotedHere && $openVotes->has($gamePlayer->id))
                                {{-- Without Hidden Agenda everyone sees the vote --}}
                                @php($openVote = $openVotes[$gamePlayer->id])
                                <span class="shrink-0 max-w-[50%] min-w-0 flex items-center gap-1"
                                      title="Voted {{ $openVote->option }}{{ $openVote->option !== 'Abstain' ? ' with ' . $openVote->influence_spent . ' influence' : '' }}">
                                    <span class="min-w-0 truncate px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">{{ $openVote->option }}</span>
                                    {{-- Influence spent on the vote --}}
                                    @if($openVote->option !== 'Abstain')
                                        <x-influence-hex :value="$openVote->influence_spent" title="{{ $openVote->influence_spent }} influence spent" sr-label="influence spent" />
                                    @endif
                                </span>
                            @elseif($hasVotedHere)
                                <span class="shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
                                    </svg>
                                    Voted
                                </span>
                            @elseif($votingOpen)
                                <span class="shrink-0 px-2 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Waiting</span>
                            @endif

                            <!-- Speaker actions for other players -->
                            @if($player->is_speaker && !$isMe)
                                <div x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false" class="relative shrink-0">
                                    <button type="button" x-on:click="open = !open" x-bind:aria-expanded="open" aria-haspopup="menu"
                                            aria-label="Actions for {{ $gamePlayer->name }}"
                                            class="p-2 -mr-1 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="5" r="1.75"></circle><circle cx="12" cy="12" r="1.75"></circle><circle cx="12" cy="19" r="1.75"></circle>
                                        </svg>
                                    </button>
                                    <div x-show="open" x-cloak x-transition.origin.top.right role="menu"
                                         class="absolute right-0 top-full mt-1 z-20 w-48 py-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black/10 dark:ring-white/10">
                                        <button type="button" role="menuitem"
                                                wire:click="transferSpeaker('{{ $gamePlayer->id }}')"
                                                wire:confirm="Are you sure you want to transfer the Speaker token to {{ $gamePlayer->name }}?"
                                                x-on:click="open = false"
                                                class="w-full text-left px-3 py-2.5 text-sm text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            Make Speaker
                                        </button>
                                        <button type="button" role="menuitem"
                                                wire:click="removePlayer('{{ $gamePlayer->id }}')"
                                                wire:confirm="Remove {{ $gamePlayer->name }} from the game? Their votes are deleted too."
                                                x-on:click="open = false"
                                                class="w-full text-left px-3 py-2.5 text-sm text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30">
                                            Remove from game
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4">
                    <x-rejoin-link :game="$game" />
                </div>
            </section>
        </div>

        <div id="tab-panel-star-chart" role="tabpanel" aria-labelledby="tab-star-chart"
             {{ $defaultTab !== 'star-chart' ? 'x-cloak' : '' }}
             x-show="tab === 'star-chart'">
            <!-- Star Chart: random system tiles that are not on the map yet -->
            @if($game->milty_draft_data)
                <x-star-chart :draws="$starChartDraws" :pool-size="$starChartPoolSize" :is-speaker="$player->is_speaker" />
            @endif
        </div>

        <div id="tab-panel-speaker" role="tabpanel" aria-labelledby="tab-speaker"
             {{ $defaultTab !== 'speaker' ? 'x-cloak' : '' }}
             x-show="tab === 'speaker'">
            <!-- Speaker Controls -->
            @if($player->is_speaker)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6 space-y-4">
                    <!-- Header -->
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Speaker Settings</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                @if($currentAgenda?->status === 'voting' && $currentAgenda->reveal_step)
                                    Resolve action cards with the agenda below before voting opens.
                                @elseif($currentAgenda?->status === 'voting')
                                    Voting is open.
                                @else
                                    Reveal the next agenda when you are ready.
                                @endif
                            </p>
                        </div>

                        @if($currentAgenda?->status === 'voting' && !$currentAgenda->reveal_step)
                            <button wire:click="endVoting"
                                    wire:confirm="Are you sure you want to end voting? {{ $game->show_results ? 'Every player will see the results.' : 'The results will only be visible to you.' }}"
                                    class="w-full sm:w-auto bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                                End Voting
                            </button>
                        @endif
                    </div>

                    @php($sectionHeading = 'text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2')
                    @php($sectionPanel = 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700 overflow-hidden')

                    <!-- Voting rules -->
                    <section aria-labelledby="speaker-voting">
                        <h3 id="speaker-voting" class="{{ $sectionHeading }}">Voting</h3>
                        <div class="{{ $sectionPanel }}">
                            {{-- Law: Representative Government --}}
                            <x-setting-switch action="toggleRepresentativeGovernment" :on="$game->representative_government"
                                              label="Representative Government is in effect"
                                              :description="($game->absol_agendas ? 'Each player casts 1 vote on each agenda instead of spending influence, the player who controls Mecatol Rex up to 2. ' : 'Each player casts 1 vote on each agenda instead of spending influence. ') . 'Turned on when the law passes For.'" />

                            {{-- Absol's Representative Government: the player who controls Mecatol Rex may cast 2 votes --}}
                            @if($game->absol_agendas && $game->representative_government)
                                <div class="p-3 sm:px-4">
                                    <label for="mecatolRexPlayer" class="block font-medium text-gray-900 dark:text-white">Controls Mecatol Rex</label>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">This player may cast 2 votes on each agenda under Representative Government.</p>
                                    <select id="mecatolRexPlayer" wire:change="setMecatolRexPlayer($event.target.value)"
                                            class="mt-2 {{ $inputClass }}">
                                        <option value="">Nobody</option>
                                        @foreach($players->sortBy('name') as $gamePlayer)
                                            <option value="{{ $gamePlayer->id }}" @selected($game->mecatol_rex_player_id === $gamePlayer->id)>{{ $gamePlayer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Absol's law: Term Limits, only with Absol's agendas --}}
                            @if($game->absol_agendas)
                                <x-setting-switch action="toggleTermLimits" :on="$game->term_limits"
                                                  label="Term Limits is in effect"
                                                  description="After each agenda you see if the speaker token moves to another player. Turned on when the law passes For." />
                            @endif

                            {{-- Event card Hidden Agenda: on by default, as secret voting is what this app is for --}}
                            @php($agendaOpen = $currentAgenda?->status === 'voting')
                            <x-setting-switch action="toggleHiddenAgenda" :on="$game->hidden_agenda" :disabled="$agendaOpen"
                                              label="Hidden Agenda"
                                              :description="'Votes are secret: only the totals are revealed. Off: every player sees how each player votes. '
                                                  . ($agendaOpen ? 'Can be changed after this agenda.' : 'Applies from the next agenda.')" />

                            {{-- Players enter their influence when the agenda phase starts, votes are taken from it --}}
                            <x-setting-switch action="toggleManagedInfluence" :on="$game->managed_influence" :disabled="$agendaOpen"
                                              label="Managed influence"
                                              :warning="$game->hidden_agenda ? 'Hidden Agenda is on, but every player sees the influence of the others go down when they vote. This can show how much someone voted with, and hint at who voted for what.' : null"
                                              :description="'Start the agenda phase to ask every player for their influence. Their votes are taken from it automatically. '
                                                  . ($agendaOpen ? 'Can be changed after this agenda.' : '')" />

                            {{-- Off: the Speaker announces the results --}}
                            <x-setting-switch action="toggleShowResults" :on="$game->show_results"
                                              label="Show results to all players"
                                              description="When voting ends, every player sees the results. Off: only you see them and you announce them." />
                        </div>
                    </section>

                    <!-- Agenda deck -->
                    <section aria-labelledby="speaker-agendas">
                        <h3 id="speaker-agendas" class="{{ $sectionHeading }}">Agendas</h3>
                        <div class="{{ $sectionPanel }}">
                            {{-- Absol's Lab agendas replace the official ones --}}
                            <x-setting-switch action="toggleAbsolAgendas" :on="$game->absol_agendas"
                                              label="Play with Absol's agendas"
                                              description="The agenda list shows the agendas of Absol's Lab instead of the official agendas." />
                        </div>
                    </section>

                    <!-- Map -->
                    <section aria-labelledby="speaker-map">
                        <h3 id="speaker-map" class="{{ $sectionHeading }}">Map</h3>
                        <div class="{{ $sectionPanel }}">
                            {{-- Milty or TI4 Lab draft URL, or a map string --}}
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between p-3 sm:px-4">
                                <span>
                                    <span class="block font-medium text-gray-900 dark:text-white">Map data</span>
                                    <span class="block text-sm text-gray-600 dark:text-gray-400">
                                        @if($game->milty_draft_data)
                                            Used for planet agendas, faction choices and Star Chart.
                                        @else
                                            No map yet. Add a draft URL or map string to use planet agendas, factions and Star Chart.
                                        @endif
                                    </span>
                                </span>
                                <livewire:update-milty-url :game="$game" :player="$player" :key="'map-' . $game->id" />
                            </div>

                            {{-- Thunder's Edge: The Fracture --}}
                            @if($game->isThundersEdgeGame())
                                <x-setting-switch action="toggleFracture" :on="$game->fracture_in_play"
                                                  label="The Fracture is in play"
                                                  description="Adds Cocytus, Lethe, Phlegethon and Styx to planet agendas." />
                            @endif
                        </div>
                    </section>
                </div>
            @endif
        </div>
    </div>
</div>
