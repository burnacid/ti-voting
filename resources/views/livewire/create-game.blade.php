<div class="max-w-md mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Create New Game</h2>

    <form wire:submit="createGame" class="space-y-4">
        <div>
            <label for="gameName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Game Name
            </label>
            <input type="text"
                   id="gameName"
                   wire:model="gameName"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                   placeholder="Enter game name">
            @error('gameName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="playerName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Your Name
            </label>
            <input type="text"
                   id="playerName"
                   wire:model="playerName"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                   placeholder="Enter your name">
            @error('playerName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                Create Game
            </button>
        </div>
    </form>
</div>
