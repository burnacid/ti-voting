@props(['on' => false, 'action', 'label', 'description' => null, 'disabled' => false])

{{-- A setting row with an on/off switch. The whole row is the button, so it is easy to tap on a phone --}}
@php($id = 'switch-' . $action)
<button type="button" role="switch" aria-checked="{{ $on ? 'true' : 'false' }}"
        aria-labelledby="{{ $id }}-label" @if($description) aria-describedby="{{ $id }}-description" @endif
        wire:click="{{ $action }}" wire:loading.attr="disabled" wire:target="{{ $action }}" @disabled($disabled)
        {{ $attributes->class('group w-full flex items-center justify-between gap-4 p-3 sm:px-4 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:bg-transparent transition-colors') }}>
    <span class="min-w-0">
        <span id="{{ $id }}-label" class="block font-medium text-gray-900 dark:text-white">{{ $label }}</span>
        @if($description)
            <span id="{{ $id }}-description" class="block text-sm text-gray-600 dark:text-gray-400">{{ $description }}</span>
        @endif
    </span>
    <span aria-hidden="true"
          class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors {{ $on ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600' }}">
        <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform {{ $on ? 'translate-x-6' : 'translate-x-1' }}"></span>
    </span>
</button>
