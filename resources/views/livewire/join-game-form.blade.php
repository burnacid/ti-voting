<div>
    {{-- A good traveler has no fixed plans and is not intent upon arriving. --}}
    <form wire:submit="joinGame" class="space-y-4">
    <div>
        <label for="gameCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Game Code
        </label>
        <input type="text"
               id="gameCode"
               wire:model="gameCode"
               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white uppercase font-mono"
               placeholder="Enter 6-character code"
               maxlength="6">
        @error('gameCode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="playerName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Your Name
        </label>
        <input type="text"
               id="playerName"
               wire:model="playerName"
               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white"
               placeholder="Enter your name">
        @error('playerName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="faction" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Faction (Optional)
        </label>
        <input type="text"
               id="faction"
               wire:model="faction"
               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white"
               placeholder="e.g., The Emirates of Hacan">
        @error('faction') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <button type="submit"
            class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
        Join Game
    </button>
</form>
</div>
