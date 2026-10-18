{{-- Install as a web app: the browser's prompt on Android, instructions on iPhone. Hidden in the installed app and once dismissed --}}
<div x-data="{
        prompt: window.deferredInstallPrompt ?? null,
        installed: window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true,
        ios: /iphone|ipad|ipod/i.test(navigator.userAgent),
        dismissed: (() => { try { return localStorage.getItem('install-app-dismissed') === '1' } catch { return false } })(),
        async install() {
            this.prompt.prompt();
            await this.prompt.userChoice;
            this.prompt = window.deferredInstallPrompt = null;
        },
        dismiss() {
            this.dismissed = true;
            try { localStorage.setItem('install-app-dismissed', '1') } catch {}
        },
     }"
     x-on:install-available.window="prompt = window.deferredInstallPrompt"
     x-on:appinstalled.window="installed = true"
     x-show="!installed && !dismissed && (prompt || ios)"
     x-cloak
     {{ $attributes->merge(['class' => 'relative flex flex-col sm:flex-row sm:items-center gap-3 text-left bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-md p-4 pr-10']) }}>
    <div class="flex flex-1 items-center gap-3 min-w-0">
        <img src="/app-icons/icon-192.png" alt="" class="w-12 h-12 shrink-0">
        <div class="min-w-0">
            <p class="font-semibold text-gray-900 dark:text-white">Add it to your home screen</p>
            <p x-show="prompt" class="text-sm text-gray-600 dark:text-gray-300">Open it like an app, full screen and one tap away during the game.</p>
            <p x-show="!prompt && ios" class="text-sm text-gray-600 dark:text-gray-300">
                In Safari, tap the Share button
                <svg class="inline w-4 h-4 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-label="Share"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0-12l-4 4m4-4l4 4M5 13v6a2 2 0 002 2h10a2 2 0 002-2v-6"/></svg>
                and choose &ldquo;Add to Home Screen&rdquo;.
            </p>
        </div>
    </div>
    <button type="button" x-show="prompt" x-on:click="install()"
            class="w-full sm:w-auto shrink-0 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
        Install app
    </button>
    <button type="button" x-on:click="dismiss()" aria-label="Hide"
            class="absolute top-2 right-2 p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
