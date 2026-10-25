@props(['value', 'title' => null, 'srLabel' => 'influence', 'small' => false])

{{-- A number in a blue hexagon, like the influence value on a TI4 planet card --}}
<span {{ $attributes->class([$small ? 'w-8 h-8 text-xs' : 'w-9 h-9 text-sm', 'shrink-0 relative grid place-items-center font-bold tabular-nums text-gray-900 dark:text-white']) }}
      title="{{ $title ?? $value . ' ' . $srLabel }}">
    <svg class="absolute inset-0 w-full h-full text-blue-600 dark:text-blue-400" viewBox="0 0 36 36" fill="none" aria-hidden="true">
        <polygon points="18,2.5 31.4,10.25 31.4,25.75 18,33.5 4.6,25.75 4.6,10.25" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"></polygon>
    </svg>
    <span class="relative">{{ $value }}<span class="sr-only"> {{ $srLabel }}</span></span>
</span>
