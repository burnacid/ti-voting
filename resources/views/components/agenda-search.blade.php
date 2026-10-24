@props(['agendas', 'inputClass'])

@php
    $options = collect($agendas)->map(fn ($agenda) => [
        'name' => $agenda['Name'],
        'type' => ucfirst($agenda['Type'] ?? ''),
        'effect' => $agenda['Effect'] ?? '',
    ])->values();
@endphp

{{-- Searchable dropdown of the preset agendas, sets the Livewire property selectedPresetAgenda --}}
<div wire:ignore
     x-data="{
        agendas: @js($options),
        query: '',
        open: false,
        active: 0,
        init() {
            this.query = this.$wire.selectedPresetAgenda ?? '';
            this.$wire.$watch('selectedPresetAgenda', (value) => this.query = value ?? '');
        },
        get results() {
            const query = this.query.trim().toLowerCase();
            // Show every agenda while nothing is typed or the chosen agenda is shown
            if (!query || this.agendas.some((agenda) => agenda.name === this.query)) {
                return this.agendas;
            }
            const byName = this.agendas.filter((agenda) => agenda.name.toLowerCase().includes(query));
            const byEffect = this.agendas.filter((agenda) => !byName.includes(agenda) && agenda.effect.toLowerCase().includes(query));
            return [...byName, ...byEffect];
        },
        show() {
            this.open = true;
            this.active = Math.max(0, this.results.findIndex((agenda) => agenda.name === this.query));
            this.$nextTick(() => this.scrollToActive());
        },
        close() {
            this.open = false;
            this.query = this.$wire.selectedPresetAgenda ?? '';
        },
        move(step) {
            if (!this.open) {
                return this.show();
            }
            const count = this.results.length;
            this.active = count ? (this.active + step + count) % count : 0;
            this.scrollToActive();
        },
        scrollToActive() {
            this.$refs.list?.children[this.active]?.scrollIntoView({ block: 'nearest' });
        },
        choose(agenda) {
            this.query = agenda.name;
            this.open = false;
            this.$wire.set('selectedPresetAgenda', agenda.name);
        },
     }"
     x-on:click.outside="close()"
     {{ $attributes->merge(['class' => 'relative min-w-0 flex-1']) }}>
    <input type="text"
           id="selectedPresetAgenda"
           x-ref="input"
           x-model="query"
           role="combobox"
           autocomplete="off"
           aria-autocomplete="list"
           aria-controls="preset-agenda-list"
           x-bind:aria-expanded="open"
           x-bind:aria-activedescendant="open && results[active] ? 'preset-agenda-' + active : null"
           placeholder="Search agendas, e.g. Mutiny or Mecatol"
           x-on:focus="$event.target.select(); show()"
           x-on:input="open = true; active = 0"
           x-on:keydown.arrow-down.prevent="move(1)"
           x-on:keydown.arrow-up.prevent="move(-1)"
           x-on:keydown.enter="if (open && results[active]) { $event.preventDefault(); choose(results[active]) }"
           x-on:keydown.escape="close()"
           x-on:keydown.tab="close()"
           class="{{ $inputClass }} pr-10">

    <button type="button"
            tabindex="-1"
            aria-label="Show all agendas"
            x-on:click="open ? close() : ($refs.input.focus(), show())"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 dark:text-gray-400">
        <svg class="h-4 w-4 transition-transform" x-bind:class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <ul x-show="open"
        x-cloak
        x-ref="list"
        id="preset-agenda-list"
        role="listbox"
        class="absolute z-20 mt-1 w-full max-h-72 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-lg py-1">
        <template x-for="(agenda, index) in results" x-bind:key="agenda.name">
            <li role="option"
                x-bind:id="'preset-agenda-' + index"
                x-bind:aria-selected="agenda.name === $wire.selectedPresetAgenda"
                x-on:mousedown.prevent="choose(agenda)"
                x-on:mouseenter="active = index"
                x-bind:class="index === active ? 'bg-blue-50 dark:bg-blue-900/40' : ''"
                class="flex items-center justify-between gap-3 px-3 py-2.5 cursor-pointer">
                <span class="text-sm text-gray-900 dark:text-white" x-bind:class="agenda.name === $wire.selectedPresetAgenda && 'font-semibold'" x-text="agenda.name"></span>
                <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400" x-text="agenda.type"></span>
            </li>
        </template>
    </ul>
    <p x-show="open && results.length === 0" x-cloak
       class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-lg px-3 py-2.5 text-sm text-gray-500 dark:text-gray-400">
        No agendas match &ldquo;<span x-text="query"></span>&rdquo;
    </p>
</div>
