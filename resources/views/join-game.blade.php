
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Join Game - TI4 Hidden Agenda</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto">
            <div class="text-center mb-8">
                <a href="{{ route('home') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    ← Back to Home
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mt-4">
                    Join Game
                </h1>
                @if(isset($game))
                    <p class="text-gray-600 dark:text-gray-300 mt-2">
                        Joining: <span class="font-semibold">{{ $game->name }}</span>
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Game Code: <span class="font-mono font-bold">{{ $game->code }}</span>
                    </p>
                @else
                    <p class="text-gray-600 dark:text-gray-300 mt-2">
                        Enter a game code to join a TI4 session
                    </p>
                @endif
            </div>

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <livewire:join-game-form :game-code="$code ?? ''" />
            </div>
        </div>
    </div>
</body>
</html>
