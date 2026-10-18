<div>
    @php($inputClass = 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white')

    @if($rejoinStatus === 'waiting')
        <!-- Waiting until another player confirms who this is -->
        <div wire:poll.3s="checkRejoin" class="space-y-4 text-center">
            <div class="mx-auto h-8 w-8 rounded-full border-4 border-green-200 border-t-green-600 animate-spin" aria-hidden="true"></div>
            <div>
                <p class="font-semibold text-gray-900 dark:text-white">Waiting for another player</p>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                    Ask someone at the table to open the game and confirm that you are {{ $playerName }}. You are let in as soon as they do.
                </p>
            </div>
            <button type="button" wire:click="cancelRejoin"
                    class="text-sm text-gray-600 dark:text-gray-300 hover:underline">
                Cancel
            </button>
        </div>
    @else
        @if($rejoinStatus === 'declined')
            <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 rounded-md p-3 text-sm">
                Your request to rejoin was declined.
            </div>
        @elseif($rejoinStatus === 'expired')
            <div class="mb-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200 rounded-md p-3 text-sm">
                Nobody confirmed your request in time. You can ask again.
            </div>
        @endif

        @if($rejoin)
            <!-- Rejoin as an existing player -->
            <form wire:submit="requestRejoin" class="space-y-4">
                <div>
                    <label for="playerName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Who are you?
                    </label>
                    <select id="playerName" wire:model="playerName" class="{{ $inputClass }}">
                        <option value="">Choose your name</option>
                        @foreach($gamePlayers as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('playerName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Another player has to confirm it is you before you are let back in.
                    </p>
                </div>

                <button type="submit"
                        @disabled($gamePlayers->isEmpty())
                        class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-medium py-3 sm:py-2 px-4 rounded-md">
                    Ask to rejoin
                </button>
                <a href="{{ route('join-group', $gameCode) }}" class="block text-center text-sm text-gray-600 dark:text-gray-300 hover:underline">
                    I am a new player
                </a>
            </form>
        @else
            <form wire:submit="joinGame" class="space-y-4">
                <div>
                    <label for="gameCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Game Code
                    </label>
                    <input type="text"
                           id="gameCode"
                           wire:model="gameCode"
                           class="{{ $inputClass }} uppercase font-mono"
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
                           class="{{ $inputClass }}"
                           placeholder="Enter your name">
                    @error('playerName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                    @if($nameTaken)
                        <div class="mt-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-3">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                Are you {{ $playerName }} and lost access, for example on another phone? Ask to rejoin and another player confirms it is you.
                            </p>
                            <button type="button" wire:click="requestRejoin"
                                    class="mt-2 w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded-md">
                                Ask to rejoin as {{ $playerName }}
                            </button>
                        </div>
                    @endif
                </div>

                <div>
                    <label for="faction" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Faction (Optional)
                    </label>
                    <input type="text"
                           id="faction"
                           wire:model="faction"
                           class="{{ $inputClass }}"
                           placeholder="e.g., The Emirates of Hacan">
                    @error('faction') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    Join Game
                </button>
                @if(strlen($gameCode) === 6)
                    <a href="{{ route('game.rejoin', $gameCode) }}" class="block text-center text-sm text-gray-600 dark:text-gray-300 hover:underline">
                        Already in this game and lost access? Rejoin
                    </a>
                @endif
            </form>
        @endif
    @endif
</div>
