@props([
    'label' => 'در حال بارگذاری...',
    'size' => 'h-4 w-4',
])

<div {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2']) }} role="status" aria-live="polite">
    <span class="{{ $size }} animate-spin rounded-full border-2 border-current/25 border-t-current" aria-hidden="true"></span>
    <span>{{ $label }}</span>
    <span class="sr-only">در حال بارگذاری</span>
</div>
