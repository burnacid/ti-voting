<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $game->name }} - TI4 Hidden Agenda</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Game Header -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $game->name }}
                        </h1>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                            <p class="text-gray-600 dark:text-gray-300">
                                Game Code: <span class="font-mono font-bold">{{ $game->code }}</span>
                            </p>
                            @if($game->milty_url)
                                <a href="{{ $game->milty_url }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    View Map Draft
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Playing as: <span class="font-semibold text-gray-900 dark:text-white">{{ $player->name }}</span>
                        </p>
                        @if($player->faction)
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Faction: <span class="font-semibold text-gray-900 dark:text-white">{{ $player->faction }}</span>
                            </p>
                        @endif
                        @if($player->is_speaker)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                Speaker
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Game Content -->
            <livewire:game-dashboard :game="$game" :player="$player" />
        </div>
    </div>

    <!-- Flash Messages -->
    <livewire:flash-messages />
</body>
</html>
