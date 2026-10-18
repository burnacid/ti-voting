@props(['player'])

{{-- Link that signs a browser in as this player, to share with a player who lost their session --}}
<div x-data="{ open: false, copied: false }" class="text-xs">
    <button type="button" x-on:click="open = !open"
            class="text-blue-600 dark:text-blue-400 hover:underline"
            x-text="open ? 'Hide rejoin link' : 'Rejoin link'">Rejoin link</button>

    <div x-show="open" x-cloak class="mt-2 space-y-1">
        <p class="text-gray-600 dark:text-gray-400">
            Send this link to {{ $player->name }} if they lost access. Anyone with the link can play as them.
        </p>
        <div class="flex gap-2">
            <input type="text" readonly value="{{ $player->rejoinUrl() }}" x-ref="link" x-on:focus="$event.target.select()"
                   aria-label="Rejoin link for {{ $player->name }}"
                   class="min-w-0 flex-1 px-2 py-1.5 font-mono text-xs border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800">
            <button type="button"
                    x-on:click="navigator.clipboard.writeText($refs.link.value).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    class="shrink-0 font-medium bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md transition-colors"
                    x-text="copied ? 'Copied' : 'Copy'">Copy</button>
        </div>
    </div>
</div>
