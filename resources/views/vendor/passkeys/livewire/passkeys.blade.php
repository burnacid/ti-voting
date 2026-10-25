<div x-data="{ supported: true, error: null }" x-init="supported = window.browserSupportsWebAuthn ? window.browserSupportsWebAuthn() : false"
     x-on:passkey-failed.window="error = $event.detail.message">
    <p x-show="!supported" x-cloak class="mb-3 text-sm text-amber-700 dark:text-amber-400">This browser can't create passkeys.</p>

    <form id="passkeyForm" wire:submit="validatePasskeyProperties" x-show="supported" x-on:submit="error = null"
          class="flex flex-col sm:flex-row sm:items-end gap-2">
        <div class="flex-1 sm:max-w-xs">
            <label for="passkey-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name of the new passkey</label>
            <input id="passkey-name" autocomplete="off" type="text" wire:model="name" placeholder="e.g. My phone"
                   class="w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700">
        </div>
        <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
            Add passkey
        </button>
    </form>
    @error('name') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
    @error('name', 'passkeyForm') <p class="mt-1 text-red-500 text-sm">{{ $message }}</p> @enderror
    <p x-show="error" x-text="error" x-cloak class="mt-1 text-red-500 text-sm"></p>

    @if($passkeys->isNotEmpty())
        <ul class="mt-4 divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg">
            @foreach($passkeys as $passkey)
                <li wire:key="passkey-{{ $passkey->id }}" class="flex items-center justify-between gap-3 p-3">
                    <div class="min-w-0">
                        <p class="font-medium truncate">{{ $passkey->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Added {{ $passkey->created_at->diffForHumans() }} · {{ $passkey->last_used_at ? 'last used ' . $passkey->last_used_at->diffForHumans() : 'not used yet' }}
                        </p>
                    </div>
                    <button type="button" wire:click="deletePasskey({{ $passkey->id }})"
                            wire:confirm="Delete the passkey {{ $passkey->name }}? You can no longer log in with it."
                            class="shrink-0 text-xs font-medium bg-gray-200 hover:bg-red-600 hover:text-white dark:bg-gray-700 px-3 py-2 rounded-md transition-colors">
                        Delete
                    </button>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No passkeys yet.</p>
    @endif
</div>

@include('passkeys::livewire.partials.createScript')
