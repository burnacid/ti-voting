<div>
    <button wire:click="openModal"
            class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
        Change Map
    </button>

    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Update Map</h2>

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
