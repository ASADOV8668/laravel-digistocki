@props([
    'type' => 'info',
    'dismissible' => true,
])

@php
    $alertId = 'flowbite-alert-'.uniqid();
    $styles = [
        'success' => ['border-success/20', 'bg-success/10', 'text-success', 'heroicon-o-check-circle', 'status'],
        'danger' => ['border-error/20', 'bg-error/10', 'text-error', 'heroicon-o-exclamation-triangle', 'alert'],
        'warning' => ['border-warning/20', 'bg-warning/10', 'text-amber-900', 'heroicon-o-exclamation-triangle', 'alert'],
        'info' => ['border-info/20', 'bg-info/10', 'text-info', 'heroicon-o-information-circle', 'status'],
    ][$type] ?? ['border-info/20', 'bg-info/10', 'text-info', 'heroicon-o-information-circle', 'status'];
@endphp

<div id="{{ $alertId }}" {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-lg border p-4 text-sm font-bold {$styles[2]} {$styles[0]} {$styles[1]}"]) }} role="{{ $styles[4] }}" aria-live="polite">
    <x-dynamic-component :component="$styles[3]" class="mt-0.5 h-5 w-5 shrink-0" />
    <div class="min-w-0 flex-1">{{ $slot }}</div>
    @if ($dismissible)
        <button type="button" data-dismiss-target="#{{ $alertId }}" aria-label="بستن پیام" class="-mx-1 -my-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-transparent text-current/60 transition hover:bg-black/5 hover:text-current focus:outline-none focus:ring-2 focus:ring-current/30">
            <x-heroicon-o-x-mark class="h-4 w-4" />
        </button>
    @endif
</div>
