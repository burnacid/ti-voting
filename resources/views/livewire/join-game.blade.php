<form wire:submit="joinGame" class="space-y-4">
    <div>
        <input type="text"
               wire:model="gameCode"
               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white uppercase font-mono text-center"
               placeholder="GAME CODE"
               maxlength="6"
               style="letter-spacing: 0.1em;">
        @error('gameCode')
        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
        @enderror
    </div>

    <button type="submit"
            class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
        Join Game
    </button>
</form>
