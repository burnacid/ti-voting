@props(['agenda'])

{{-- Digital version of a printed TI4 agenda card --}}
{{-- Agendas of Absol's Lab get their own card style, so players see the difference --}}
<div {{ $attributes->class(['agenda-card', 'agenda-card-absol' => $agenda->isAbsol(), 'agenda-card-' . $agenda->type => $agenda->type]) }}>
    <h2 class="agenda-card-title">{{ $agenda->title }}</h2>
    <div class="agenda-card-type">
        {{-- Agendas created before the type was stored have none --}}
        <span>{{ $agenda->type ?? '' }}</span>
    </div>
    <div class="agenda-card-body">
        @if($agenda->elect_label)
            <p class="agenda-card-elect">{{ $agenda->elect_label }}</p>
        @endif
        {!! $agenda->formatted_description !!}
    </div>
    {{-- Deck the agenda comes from, e.g. Prophecy of Kings --}}
    @if($agenda->source_label)
        <p class="agenda-card-source">{{ $agenda->source_label }}</p>
    @endif
</div>
