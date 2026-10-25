<div class="max-w-sm mx-auto mt-8">
    <img src="/logo.svg" alt="" class="mx-auto mb-6 w-24 h-24 drop-shadow-lg">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
    <h1 class="text-xl font-bold mb-4">Admin login</h1>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
            <input type="email" id="email" wire:model="email" autocomplete="username" autofocus
                   class="w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700">
            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
            <input type="password" id="password" wire:model="password" autocomplete="current-password"
                   class="w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700">
            @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" wire:model="remember" class="h-4 w-4 rounded text-blue-600">
            Remember me
        </label>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-3 sm:py-2 px-4 rounded-lg transition-colors">
            Log in
        </button>
    </form>
    </div>
</div>
