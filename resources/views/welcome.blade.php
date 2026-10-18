<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TI4 Hidden Agenda</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto text-center">
            <div class="mb-12">
                <h1 class="text-4xl md:text-6xl font-bold text-gray-900 dark:text-white mb-4">
                    TI4 Hidden Agenda
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
                    Secret voting for Twilight Imperium 4th Edition agenda phase
                </p>
            </div>

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
