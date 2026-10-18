@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} - TI4 Hidden Agenda Admin</title>
    @include('partials.icons')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen text-gray-900 dark:text-white">
    @auth
        <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-6xl mx-auto px-4 flex flex-wrap items-center gap-x-4 gap-y-2 py-3">
                <a href="{{ route('admin.dashboard') }}" class="font-bold mr-auto">TI4 Admin</a>
                @foreach(['admin.dashboard' => 'Dashboard', 'admin.games' => 'Games', 'admin.logs' => 'Logs'] as $route => $label)
                    <a href="{{ route($route) }}"
                       @class([
                           'text-sm font-medium px-2 py-1 rounded-md',
                           'bg-gray-100 dark:bg-gray-700' => request()->routeIs($route . '*'),
                           'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' => !request()->routeIs($route . '*'),
                       ])>
                        {{ $label }}
                    </a>
                @endforeach
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium px-2 py-1 text-gray-600 dark:text-gray-300 hover:text-red-600 dark:hover:text-red-400">
                        Log out
                    </button>
                </form>
            </div>
        </nav>
    @endauth

    <main class="max-w-6xl mx-auto px-4 py-6 space-y-6">
        @if(session('status'))
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 rounded-lg p-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    @include('partials.footer')
</body>
</html>
