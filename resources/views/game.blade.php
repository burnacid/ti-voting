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
                        <p class="text-gray-600 dark:text-gray-300">
                            Game Code: <span class="font-mono font-bold">{{ $game->code }}</span>
                        </p>
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
</body>
</html>
