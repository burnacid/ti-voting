@props(['player'])

@php
    // Colours written out in full so Tailwind finds the classes, picked from the player's name
    $colors = [
        'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200',
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200',
        'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200',
        'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-200',
        'bg-violet-100 text-violet-800 dark:bg-violet-900/60 dark:text-violet-200',
        'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/60 dark:text-cyan-200',
    ];
@endphp

<span {{ $attributes->class(['shrink-0 w-10 h-10 rounded-full grid place-items-center text-sm font-bold', $colors[crc32($player->name) % count($colors)]]) }} aria-hidden="true">{{ $player->initials }}</span>
