<div>
    <button wire:click="openModal"
            class="shrink-0 inline-flex items-center justify-center gap-1.5 w-full sm:w-auto px-4 py-2.5 sm:py-2 rounded-lg text-sm font-semibold border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"></path>
        </svg>
        Change Map Data
    </button>

    @if($showModal)
        <div class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Change Map Data</h2>

                <form wire:submit="updateMiltyUrl">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Draft URL or Map String
                        </label>
                        <input type="text"
                               wire:model="miltyUrl"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white"
                               placeholder="Milty or TI4 Lab URL, or tile numbers">
                        @error('miltyUrl')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                        @else
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">A Milty or TI4 Lab draft URL, or a map string of tile numbers separated by spaces or commas.</p>
                        @enderror
                    </div>

                    <div class="flex gap-3 justify-end">
                        <button type="button"
                                wire:click="closeModal"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
