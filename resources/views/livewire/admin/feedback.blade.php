<section class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
        <div>
            <h1 class="text-lg font-bold">Feedback</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Sent by players with the Feedback link in the footer.</p>
        </div>
        @if($unreadCount > 0)
            <button wire:click="markAllRead"
                    class="w-full sm:w-auto shrink-0 text-sm font-medium bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 px-3 py-2 rounded-md transition-colors">
                Mark all as read ({{ $unreadCount }})
            </button>
        @endif
    </div>

    @php($inputClass = 'w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700')

    <!-- Filters -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        <select wire:model.live="type" aria-label="Type" class="{{ $inputClass }}">
            <option value="">All types ({{ $typeCounts->sum() }})</option>
            @foreach($types as $value => $label)
                <option value="{{ $value }}">{{ $label }} ({{ $typeCounts[$value] ?? 0 }})</option>
            @endforeach
        </select>
        <select wire:model.live="status" aria-label="Status" class="{{ $inputClass }}">
            <option value="">Read and unread</option>
            <option value="unread">Unread only ({{ $unreadCount }})</option>
        </select>
    </div>

    <div class="flex items-center justify-between gap-3 mt-3 mb-4 text-sm text-gray-600 dark:text-gray-400">
        <span>{{ Number::format($items->total()) }} {{ Str::plural('item', $items->total()) }}</span>
        @if($type || $status)
            <button wire:click="clearFilters" class="text-blue-600 dark:text-blue-400 hover:underline">Clear filters</button>
        @endif
    </div>

    <!-- Feedback -->
    <div class="divide-y divide-gray-200 dark:divide-gray-700 border-y border-gray-200 dark:border-gray-700">
        @forelse($items as $item)
            <article wire:key="feedback-{{ $item->id }}" @class([
                'py-3 pl-3 border-l-4',
                'border-blue-500 bg-blue-50/50 dark:bg-blue-900/10' => !$item->read_at,
                'border-transparent' => $item->read_at,
            ])>
                <!-- Type, time, sender and game -->
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                    <span @class([
                        'px-1.5 py-0.5 rounded font-semibold uppercase',
                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $item->type === 'bug',
                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $item->type === 'idea',
                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' => !in_array($item->type, ['bug', 'idea']),
                    ])>{{ $item->typeLabel() }}</span>
                    @unless($item->read_at)
                        <span class="px-1.5 py-0.5 rounded font-semibold bg-blue-600 text-white">New</span>
                    @endunless
                    <time datetime="{{ $item->created_at->toIso8601String() }}" title="{{ $item->created_at->diffForHumans() }}"
                          class="text-gray-500 dark:text-gray-400 font-mono">{{ $item->created_at->format('Y-m-d H:i:s') }}</time>
                    @if($item->name)
                        <span class="font-medium text-gray-700 dark:text-gray-200 break-all">{{ $item->name }}</span>
                    @endif
                    @if($item->game)
                        @if($existingGames->has($item->game))
                            <a href="{{ route('admin.games.show', $existingGames[$item->game]) }}"
                               class="px-1.5 py-0.5 rounded font-mono font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-800">{{ $item->game }}</a>
                        @else
                            <span class="px-1.5 py-0.5 rounded font-mono bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300" title="This game was deleted">{{ $item->game }}</span>
                        @endif
                    @endif
                </div>

                <!-- Message -->
                <p class="mt-1 whitespace-pre-line break-words">{{ $item->message }}</p>

                <!-- Page and browser -->
                @if($item->url || $item->user_agent)
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 break-all">
                        {{ $item->url ? parse_url($item->url, PHP_URL_PATH) ?: '/' : '' }}
                        @if($item->url && $item->user_agent) · @endif
                        {{ $item->user_agent }}
                    </div>
                @endif

                <!-- Actions -->
                <div class="mt-2 flex flex-wrap gap-3 text-sm">
                    @if($item->read_at)
                        <button wire:click="markUnread({{ $item->id }})" class="text-blue-600 dark:text-blue-400 hover:underline">Mark as unread</button>
                    @else
                        <button wire:click="markRead({{ $item->id }})" class="text-blue-600 dark:text-blue-400 hover:underline">Mark as read</button>
                    @endif
                    <button wire:click="delete({{ $item->id }})" wire:confirm="Delete this feedback? This cannot be undone."
                            class="text-red-600 dark:text-red-400 hover:underline">Delete</button>
                </div>
            </article>
        @empty
            <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">No feedback found.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>
</section>
