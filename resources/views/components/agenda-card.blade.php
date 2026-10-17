@props(['agenda'])

{{-- Digital version of a printed TI4 agenda card --}}
<div {{ $attributes->merge(['class' => 'agenda-card']) }}>
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
</div>
