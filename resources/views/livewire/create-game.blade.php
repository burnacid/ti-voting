<div class="max-w-md mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
    @php($inputClass = 'w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white')

    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Create New Game</h2>

    <form wire:submit="createGame" class="space-y-4">
        <div>
            <label for="gameName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Game Name
            </label>
            <input type="text"
                   id="gameName"
                   wire:model="gameName"
                   class="{{ $inputClass }}"
                   placeholder="Enter game name">
            @error('gameName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="miltyUrl" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Draft URL or Map String <span class="font-normal text-gray-500 dark:text-gray-400">(optional)</span>
            </label>
            <input type="text"
                   id="miltyUrl"
                   wire:model.blur="miltyUrl"
                   class="{{ $inputClass }}"
                   placeholder="Milty or TI4 Lab URL, or tile numbers">
            <p wire:loading wire:target="miltyUrl" class="mt-1 text-sm text-gray-500 dark:text-gray-400">Loading the map&hellip;</p>
            @error('miltyUrl') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            @if($miltyError && !$errors->has('miltyUrl'))
                <span wire:loading.remove wire:target="miltyUrl" class="block text-red-500 text-sm">{{ $miltyError }}</span>
            @elseif($hasMiltyData)
                <span wire:loading.remove wire:target="miltyUrl" class="block mt-1 text-sm text-green-700 dark:text-green-400">
                    {{ ($miltyDraftData['source'] ?? null) === 'map_string' ? 'Map' : 'Draft' }} loaded with {{ $factions->count() }} {{ Str::plural('faction', $factions->count()) }}.
                </span>
            @else
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    A draft from <a href="https://milty.shenanigans.be" target="_blank" rel="noopener noreferrer" class="underline">Milty</a> or <a href="https://tidraft.com" target="_blank" rel="noopener noreferrer" class="underline">TI4 Lab</a>, or a map string like <span class="font-mono">41 50 73 31 &hellip;</span>, lets planet agendas list the planets in your game and players pick their faction.
                </p>
            @endif
        </div>

        <div>
            <label for="playerName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Your Name
            </label>
            <input type="text"
                   id="playerName"
                   wire:model="playerName"
                   class="{{ $inputClass }}"
                   placeholder="Enter your name">
            @error('playerName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        @if($factions->isNotEmpty())
            <div>
                <label for="faction" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Your Faction
                </label>
                <select id="faction" wire:model="faction" class="{{ $inputClass }}">
                    <option value="">Choose the faction you drafted</option>
                    @foreach($factions as $factionName => $drafter)
                        <option value="{{ $factionName }}">{{ $factionName }}{{ $drafter ? " ({$drafter})" : '' }}</option>
                    @endforeach
                </select>
                @error('faction') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        @endif

        <div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 sm:py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                Create Game
            </button>
        </div>
    </form>
</div>
