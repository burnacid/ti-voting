<div class="space-y-6">
    @php($input = 'w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700')
    @php($primary = 'w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors')
    @php($secondary = 'w-full sm:w-auto bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors')

    <!-- Passkeys: log in without a password -->
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 sm:p-6">
        <h2 class="text-lg font-bold mb-1">Passkeys</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Log in with your fingerprint, face or device PIN instead of your password, on the login page with "Log in with a passkey".
        </p>
        <livewire:passkeys />
    </section>

    <!-- TOTP: a code from an authenticator app after the password -->
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 sm:p-6 space-y-4">
        <div>
            <h2 class="text-lg font-bold mb-1">Authenticator app</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                After your password, also ask for a code from an authenticator app, such as Google Authenticator, Microsoft Authenticator or 1Password.
            </p>
        </div>

        @if($newRecoveryCodes)
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                <p class="font-semibold text-amber-900 dark:text-amber-100">Save your recovery codes</p>
                <p class="text-sm text-amber-800 dark:text-amber-200 mt-1">
                    Use one when you can't use your authenticator app. Each code works once. They are only shown now.
                </p>
                <ul class="mt-3 grid grid-cols-2 gap-1 font-mono text-sm">
                    @foreach($newRecoveryCodes as $recoveryCode)
                        <li class="px-2 py-1 bg-white dark:bg-gray-800 rounded">{{ $recoveryCode }}</li>
                    @endforeach
                </ul>
                <button type="button" wire:click="hideRecoveryCodes" class="mt-3 {{ $secondary }}">I saved them</button>
            </div>
        @endif

        @if($user->hasTwoFactor())
            <p class="inline-flex items-center gap-2 text-sm font-medium text-green-700 dark:text-green-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path></svg>
                On since {{ $user->two_factor_confirmed_at->format('Y-m-d') }} · {{ $recoveryCodesLeft }} recovery {{ Str::plural('code', $recoveryCodesLeft) }} left
            </p>

            <div class="space-y-2">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Your password, to change this</label>
                <input type="password" id="password" wire:model="password" autocomplete="current-password" class="{{ $input }} sm:max-w-xs">
                @error('password') <span class="block text-red-500 text-sm">{{ $message }}</span> @enderror
                <div class="flex flex-col sm:flex-row gap-2 pt-1">
                    <button type="button" wire:click="regenerateRecoveryCodes" class="{{ $secondary }}">New recovery codes</button>
                    <button type="button" wire:click="disable" wire:confirm="Turn off the authenticator app? Logging in with a password then needs no code."
                            class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                        Turn off
                    </button>
                </div>
            </div>
        @elseif($settingUp && $secret)
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="shrink-0 self-center sm:self-start w-[200px] h-[200px] bg-white p-1 rounded-lg [&_svg]:w-full [&_svg]:h-full" aria-label="QR code to scan with your authenticator app">
                    {!! $qrCode !!}
                </div>
                <div class="space-y-3 min-w-0">
                    <p class="text-sm">1. Scan the QR code with your authenticator app, or enter this key:</p>
                    <p class="font-mono text-sm break-all bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-2 select-all">{{ trim(chunk_split($secret, 4, ' ')) }}</p>
                    <form wire:submit="confirmSetup" class="space-y-2">
                        <label for="code" class="block text-sm">2. Enter the 6-digit code the app shows:</label>
                        <input type="text" id="code" wire:model="code" autocomplete="one-time-code" inputmode="numeric" placeholder="123456"
                               class="{{ $input }} sm:max-w-[10rem] font-mono tracking-wider">
                        @error('code') <span class="block text-red-500 text-sm">{{ $message }}</span> @enderror
                        <div class="flex flex-col sm:flex-row gap-2 pt-1">
                            <button type="submit" class="{{ $primary }}">Turn on</button>
                            <button type="button" wire:click="cancelSetup" class="{{ $secondary }}">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <button type="button" wire:click="startSetup" class="{{ $primary }}">Set up authenticator app</button>
        @endif
    </section>
</div>
