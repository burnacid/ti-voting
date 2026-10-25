<div x-data="{ supported: true, error: null }" x-init="supported = window.browserSupportsWebAuthn ? window.browserSupportsWebAuthn() : false">
    @include('passkeys::components.partials.authenticateScript')

    <form id="passkey-login-form" method="POST" action="{{ route('passkeys.login') }}">
        @csrf
    </form>

    @if($message = session()->get('authenticatePasskey::message'))
        <p class="mb-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @endif
    <p x-show="error" x-text="error" x-cloak class="mb-3 text-sm text-red-600 dark:text-red-400"></p>

    <button type="button" x-show="supported"
            x-on:click="error = null; authenticateWithPasskey().catch(() => error = 'Logging in with a passkey did not work. Try again or use your password.')"
            class="w-full inline-flex items-center justify-center gap-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.864 4.243A7.5 7.5 0 0 1 19.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 0 0 4.5 10.5a7.464 7.464 0 0 1-1.15 3.993m1.989 3.559A11.209 11.209 0 0 0 8.25 10.5a3.75 3.75 0 1 1 7.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 0 1-3.6 9.75m6.633-4.596a18.666 18.666 0 0 1-2.485 5.33"></path>
        </svg>
        {{ $slot->isEmpty() ? 'Log in with a passkey' : $slot }}
    </button>
</div>
