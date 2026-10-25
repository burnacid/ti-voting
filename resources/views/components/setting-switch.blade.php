@props(['on' => false, 'action', 'label', 'description' => null, 'disabled' => false, 'warning' => null])

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
        @if($warning)
            <span class="mt-1 flex items-start gap-1.5 text-sm font-medium text-amber-700 dark:text-amber-400">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374L10.051 3.378c.866-1.5 3.032-1.5 3.898 0l7.354 12.748ZM12 15.75h.007v.008H12v-.008Z"></path>
                </svg>
                <span>{{ $warning }}</span>
            </span>
        @endif
    </span>
    <span aria-hidden="true"
          class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors {{ $on ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600' }}">
        <span class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform {{ $on ? 'translate-x-6' : 'translate-x-1' }}"></span>
    </span>
</button>
