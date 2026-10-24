<div x-data="{ open: false }" x-on:influence-saved.window="open = false"
     x-on:click.outside="open = false" x-on:keydown.escape.window="open = false"
     class="relative shrink-0 ml-auto">
    <!-- Available influence, as the blue influence value on a TI4 planet card. Tap to change it -->
    <button type="button" x-on:click="open = !open" x-bind:aria-expanded="open"
            aria-label="Your influence: {{ $influence === '' ? 'not entered' : $influence }}. Change it"
            class="flex flex-col items-center gap-0.5 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
        <span class="relative w-11 h-11 grid place-items-center text-base font-bold tabular-nums text-gray-900 dark:text-white">
            <svg class="absolute inset-0 w-full h-full text-blue-600 dark:text-blue-400" viewBox="0 0 36 36" fill="none" aria-hidden="true">
                <polygon points="18,2.5 31.4,10.25 31.4,25.75 18,33.5 4.6,25.75 4.6,10.25" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"></polygon>
            </svg>
            <span class="relative {{ $influence === '' ? 'text-gray-400 dark:text-gray-500' : '' }}">{{ $influence === '' ? '?' : $influence }}</span>
        </span>
        <span class="text-[11px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Influence</span>
    </button>

    <form x-show="open" x-cloak x-transition.origin.top.right wire:submit="save"
          class="absolute right-0 top-full mt-1 z-20 w-56 p-3 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black/10 dark:ring-white/10">
        <label for="influence" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Your available influence</label>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Shown to every player. Leave empty to hide it.</p>
        <div class="flex gap-2">
            <input type="number" id="influence" wire:model="influence" min="0" max="99" inputmode="numeric"
                   class="w-20 px-3 py-2 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <button type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-2 px-3 rounded-lg transition-colors">
                Save
            </button>
        </div>
        @error('influence') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </form>
</div>
