<div class="max-w-sm mx-auto mt-8">
    <img src="/logo.svg" alt="" class="mx-auto mb-6 w-24 h-24 drop-shadow-lg">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        <h1 class="text-xl font-bold mb-1">Two-factor authentication</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {{ $useRecoveryCode ? 'Enter one of your recovery codes. Each code works once.' : 'Enter the 6-digit code from your authenticator app.' }}
        </p>

        <form wire:submit="verify" class="space-y-4">
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $useRecoveryCode ? 'Recovery code' : 'Code' }}
                </label>
                <input type="text" id="code" wire:model="code" autofocus wire:key="code-{{ $useRecoveryCode ? 'recovery' : 'totp' }}"
                       @if($useRecoveryCode) autocomplete="off" placeholder="xxxxx-xxxxx" @else autocomplete="one-time-code" inputmode="numeric" placeholder="123456" @endif
                       class="w-full px-3 py-2.5 text-base sm:text-sm font-mono tracking-wider border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700">
                @error('code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                Log in
            </button>
        </form>

        <button type="button" wire:click="toggleRecoveryCode" class="mt-4 text-sm text-blue-600 dark:text-blue-400 hover:underline">
            {{ $useRecoveryCode ? 'Use a code from your authenticator app' : 'Use a recovery code' }}
        </button>
    </div>
</div>
