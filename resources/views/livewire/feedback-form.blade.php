<span x-data="{ open: false }" x-on:keydown.escape.window="open = false">
    <button type="button" x-on:click="open = true; $nextTick(() => $refs.message?.focus())"
            class="hover:text-gray-700 dark:hover:text-gray-200">
        Feedback
    </button>

    <div x-show="open" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 p-4 text-left text-base"
         x-on:click.self="open = false">
        <div role="dialog" aria-modal="true" aria-labelledby="feedback-title"
             class="w-full max-w-md max-h-full overflow-y-auto bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 id="feedback-title" class="text-xl font-bold text-gray-900 dark:text-white">Send feedback</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Ideas, bugs or anything else about TI Voting.</p>
                </div>
                <button type="button" x-on:click="open = false" aria-label="Close"
                        class="-mr-2 -mt-1 p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            @if($sent)
                <div class="mt-5 text-center">
                    <svg class="w-12 h-12 mx-auto text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                    </svg>
                    <p class="mt-2 font-semibold text-gray-900 dark:text-white">Thanks for your feedback!</p>
                    <div class="mt-5 flex flex-col sm:flex-row gap-2">
                        <button type="button" wire:click="again"
                                class="flex-1 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                            Send more
                        </button>
                        <button type="button" x-on:click="open = false; $wire.again()"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            @else
                <form wire:submit="submit" class="mt-5 space-y-4">
                    <fieldset>
                        <legend class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">What is it about?</legend>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($types as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" name="feedback-type" wire:model="type" value="{{ $value }}" class="sr-only">
                                    <span x-bind:class="$wire.type === @js($value)
                                              ? 'bg-blue-600 border-blue-600 text-white'
                                              : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                          class="block text-center text-sm font-medium py-2 rounded-lg border transition-colors">
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </fieldset>

                    <div>
                        <label for="feedback-message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Your feedback</label>
                        <textarea id="feedback-message" x-ref="message" wire:model="message" rows="5" maxlength="{{ \App\Livewire\FeedbackForm::MAX_LENGTH }}"
                                  class="w-full px-3 py-2 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
                        <div class="flex justify-between gap-2 text-sm">
                            <span>@error('message') <span class="text-red-500">{{ $message }}</span> @enderror</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 tabular-nums shrink-0"><span x-text="($wire.message || '').length">0</span>/{{ \App\Livewire\FeedbackForm::MAX_LENGTH }}</span>
                        </div>
                    </div>

                    <div>
                        <label for="feedback-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Name or contact <span class="font-normal text-gray-500 dark:text-gray-400">(optional)</span>
                        </label>
                        <input type="text" id="feedback-name" wire:model="name" maxlength="100" autocomplete="name"
                               class="w-full px-3 py-2 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Only if you would like a reply.</p>
                        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- Honeypot: hidden from people, filled in by bots --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="feedback-website">Website</label>
                        <input type="text" id="feedback-website" wire:model="website" tabindex="-1" autocomplete="off">
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:opacity-60 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                        <span wire:loading.remove wire:target="submit">Send feedback</span>
                        <span wire:loading wire:target="submit">Sending…</span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</span>
