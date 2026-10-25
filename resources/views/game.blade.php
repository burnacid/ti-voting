<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $game->name }} - TI4 Hidden Agenda</title>
    @include('partials.icons')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Back to the homepage, which lists this phone's games. The installed app has no browser back button -->
            <a href="{{ route('home') }}"
               class="inline-flex items-center gap-2 mb-4 py-1 pr-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                <img src="/favicon.svg" alt="" class="w-7 h-7">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Home
            </a>

            <!-- Game Header -->
            @php($pillClass = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors')
            <header x-data="{ invite: false, copied: null }"
                    x-on:keydown.escape.window="invite = false"
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6 mb-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white break-words">{{ $game->name }}</h1>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <!-- Game code, tap to copy -->
                            <button type="button"
                                    x-on:click="navigator.clipboard.writeText(@js($game->code)).then(() => { copied = 'code'; setTimeout(() => copied = null, 2000) })"
                                    title="Copy the game code"
                                    class="inline-flex items-center gap-2 pl-3 pr-2.5 py-1.5 rounded-full bg-gray-100 dark:bg-gray-900/60 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors">
                                <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Code</span>
                                <span class="font-mono font-bold tracking-widest">{{ $game->code }}</span>
                                <svg x-show="copied !== 'code'" class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"></path>
                                </svg>
                                <span x-show="copied === 'code'" x-cloak class="text-xs font-semibold text-green-700 dark:text-green-400">Copied</span>
                            </button>

                            <button type="button" x-on:click="invite = true" class="{{ $pillClass }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z"></path>
                                </svg>
                                Invite players
                            </button>

                            @if($game->safe_milty_url)
                                <a href="{{ $game->safe_milty_url }}" target="_blank" rel="noopener noreferrer" class="{{ $pillClass }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z"></path>
                                    </svg>
                                    View Map Draft
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Who this browser plays as -->
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 sm:min-w-56">
                        <x-player-avatar :player="$player" class="w-11 h-11" />
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Playing as</p>
                            <p class="flex items-center gap-1.5 font-semibold text-gray-900 dark:text-white">
                                <span class="truncate">{{ $player->name }}</span>
                                @if($player->is_speaker)
                                    <span class="shrink-0 px-1.5 py-0.5 rounded text-[11px] font-semibold uppercase tracking-wide bg-yellow-100 text-yellow-800 dark:bg-yellow-900/60 dark:text-yellow-200">Speaker</span>
                                @endif
                            </p>
                            @if($player->faction)
                                <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $player->faction }}</p>
                            @endif
                        </div>
                        <livewire:player-influence :game="$game" :player="$player" />
                    </div>
                </div>

                <!-- Invite players: QR code and link to the join page -->
                <div x-show="invite" x-cloak x-transition.opacity
                     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/60 p-4"
                     x-on:click.self="invite = false">
                    <div role="dialog" aria-modal="true" aria-labelledby="invite-title"
                         class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 text-center">
                        <div class="flex items-start justify-between gap-4 text-left">
                            <div>
                                <h2 id="invite-title" class="text-xl font-bold text-gray-900 dark:text-white">Invite players</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Scan with a phone camera to join {{ $game->name }}.</p>
                            </div>
                            <button type="button" x-on:click="invite = false" aria-label="Close"
                                    class="-mr-2 -mt-1 p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        {{-- Always dark on white, so phones can scan it in dark mode too --}}
                        <div class="mt-5 mx-auto w-60 max-w-full p-3 bg-white rounded-xl ring-1 ring-gray-200 [&>svg]:w-full [&>svg]:h-auto" role="img" aria-label="QR code to join this game">
                            {!! $game->joinQrSvg() !!}
                        </div>

                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Or enter the code at {{ parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost() }}</p>
                        <p class="font-mono text-3xl font-bold tracking-[0.3em] text-gray-900 dark:text-white">{{ $game->code }}</p>

                        <div class="mt-5 flex flex-col sm:flex-row gap-2">
                            <button type="button"
                                    x-on:click="navigator.clipboard.writeText(@js($game->joinUrl())).then(() => { copied = 'link'; setTimeout(() => copied = null, 2000) })"
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors"
                                    x-text="copied === 'link' ? 'Link copied' : 'Copy join link'">Copy join link</button>
                            <template x-if="'share' in navigator">
                                <button type="button"
                                        x-on:click="navigator.share({ title: @js($game->name), text: @js('Join ' . $game->name . ' with code ' . $game->code), url: @js($game->joinUrl()) }).catch(() => {})"
                                        class="flex-1 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
                                    Share
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </header>

            <x-install-app class="mb-6" />

            <!-- Game Content -->
            <livewire:game-dashboard :game="$game" :player="$player" />
        </div>
    </div>

    @include('partials.footer')

    <!-- Flash Messages -->
    <livewire:flash-messages />
</body>
</html>
