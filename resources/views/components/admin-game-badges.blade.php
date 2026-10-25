@props(['game', 'voting' => false, 'showMap' => true])

{{-- How a game is set up, for the admin: its map, expansions and the options that are on --}}
@php($pill = 'px-2 py-0.5 rounded text-xs font-medium')
<div {{ $attributes->class('flex flex-wrap gap-1.5') }}>
    @if($voting)
        <span class="{{ $pill }} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Voting now</span>
    @endif

    @if($showMap)
        @if($source = $game->mapSource())
            <span class="{{ $pill }} bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ \App\Models\Game::MAP_SOURCE_LABELS[$source] }}</span>
        @else
            <span class="{{ $pill }} bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">No map</span>
        @endif
    @endif

    @foreach($game->expansions() as $key => $label)
        <span class="{{ $pill }} {{ $key === 'te' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
            {{ $label }}{{ $key === 'te' && $game->fracture_in_play ? ' · Fracture in play' : '' }}
        </span>
    @endforeach

    @if($game->absol_agendas)
        <span class="{{ $pill }} bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">Absol's agendas</span>
    @endif
    @if($game->managed_influence)
        <span class="{{ $pill }} bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">Managed influence</span>
    @endif
    @if($game->hidden_agenda === false)
        <span class="{{ $pill }} bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">Hidden Agenda off</span>
    @endif
    @if($game->show_results)
        <span class="{{ $pill }} bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">Results shown to all</span>
    @endif
    @if($game->representative_government)
        <span class="{{ $pill }} bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Representative Government</span>
    @endif
</div>
