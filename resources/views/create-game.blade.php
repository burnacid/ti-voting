<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Game - TI4 Hidden Agenda</title>
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
                    Create New Game
                </h1>
                <p class="text-gray-600 dark:text-gray-300 mt-2">
                    Start a new TI4 Hidden Agenda session
                </p>
            </div>

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <livewire:create-game />
        </div>
    </div>
</body>
</html>
