<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TI4 Hidden Agenda</title>
    @include('partials.icons')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto text-center">
            <div class="mb-12">
                <img src="/logo.svg" alt="" class="mx-auto mb-6 w-28 h-28 md:w-36 md:h-36 drop-shadow-lg">
                <h1 class="text-4xl md:text-6xl font-bold text-gray-900 dark:text-white mb-4">
                    TI4 Hidden Agenda
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
                    Secret voting for Twilight Imperium 4th Edition agenda phase
                </p>
            </div>

            <!-- Games this browser plays in, the installed app always opens on this page -->
            @if($myPlayers->isNotEmpty())
                <section class="text-left mb-8">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">
                        {{ $myPlayers->count() === 1 ? 'Continue your game' : 'Continue one of your games' }}
                    </h2>
                    <div class="space-y-2">
                        @foreach($myPlayers as $myPlayer)
                            @php($openAgenda = $myPlayer->game->currentAgenda())
                            <div x-data="{ confirming: false }">
                            <div class="flex items-stretch gap-2">
                            <a href="{{ route('game.show', $myPlayer->game->code) }}"
                               @class([
                                   'flex flex-1 min-w-0 items-center justify-between gap-4 rounded-xl shadow-md p-4 transition-colors',
                                   'bg-blue-600 hover:bg-blue-700 text-white' => $loop->first,
                                   'bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700' => !$loop->first,
                               ])>
                                <span class="min-w-0">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="text-lg font-semibold truncate">{{ $myPlayer->game->name }}</span>
                                        @if($openAgenda)
                                            <span @class(['px-2 py-0.5 rounded-full text-xs font-medium', 'bg-white/20' => $loop->first, 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => !$loop->first])>
                                                {{ $openAgenda->isVotingOpen() ? 'Voting open' : 'Agenda revealed' }}
                                            </span>
                                        @endif
                                    </span>
                                    <span @class(['block text-sm', 'text-blue-100' => $loop->first, 'text-gray-600 dark:text-gray-400' => !$loop->first])>
                                        Playing as {{ $myPlayer->name }}{{ $myPlayer->is_speaker ? ' (Speaker)' : '' }} · <span class="font-mono">{{ $myPlayer->game->code }}</span>
                                    </span>
                                </span>
                                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                            <button type="button"
                                    x-on:click="confirming = !confirming"
                                    x-bind:aria-expanded="confirming"
                                    aria-label="Remove {{ $myPlayer->game->name }} from this list"
                                    title="Remove from this list"
                                    class="px-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-400 hover:text-red-600 dark:hover:text-red-400 shadow-md transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                            </div>

                            <!-- Confirmation: only removes the game from this phone's list, the player stays in the game -->
                            <form x-show="confirming" x-cloak method="POST" action="{{ route('my-games.forget', $myPlayer->game->code) }}"
                                  class="mt-2 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-3">
                                @csrf
                                <p class="text-sm text-red-800 dark:text-red-200">
                                    Remove <strong>{{ $myPlayer->game->name }}</strong> from this list? You stay in the game and can get back in through its rejoin link.
                                </p>
                                <div class="mt-2 flex flex-col sm:flex-row gap-2">
                                    <button type="submit"
                                            class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white text-sm font-semibold py-2.5 sm:py-2 px-4 rounded-lg transition-colors">
                                        Remove from list
                                    </button>
                                    <button type="button" x-on:click="confirming = false"
                                            class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 text-sm font-semibold py-2.5 sm:py-2 px-4 rounded-lg transition-colors">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Install as a web app: the browser's prompt on Android, instructions on iPhone -->
            <x-install-app class="mb-8" />

            <div class="grid md:grid-cols-2 gap-8 mb-12">
                <!-- Create Game Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-gray-700">
                    <div class="text-blue-600 dark:text-blue-400 mb-4">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                        Start New Game
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Create a new TI4 game session and become the Speaker
                    </p>
                    <a href="{{ route('create-game') }}"
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                        Create Game
                    </a>
                </div>

                <!-- Join Game Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-gray-700">
                    <div class="text-green-600 dark:text-green-400 mb-4">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                        Join Game
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Enter a game code to join an existing TI4 session
                    </p>
                    <livewire:join-game />
                </div>
            </div>

            <!-- How It Works Section -->
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                    How It Works
                </h2>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-purple-600 dark:text-purple-400 mb-3">
                            <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">1. Join Session</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Players join using a 6-character game code. No accounts needed.
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="text-orange-600 dark:text-orange-400 mb-3">
                            <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">2. Secret Voting</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Vote on agendas secretly with your influence tokens. No one sees your vote.
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="text-red-600 dark:text-red-400 mb-3">
                            <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">3. Speaker Reveals</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Only the Speaker sees the final results after all players have voted.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('partials.footer')
</body>
</html>
