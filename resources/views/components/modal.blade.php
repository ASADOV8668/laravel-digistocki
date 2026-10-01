@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl'
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<div id="{{ $name }}"
    tabindex="-1"
    aria-hidden="{{ $show ? 'false' : 'true' }}"
    data-modal-backdrop="static"
    role="dialog"
    aria-modal="true"
    class="fixed inset-0 z-50 {{ $show ? '' : 'hidden ' }}h-full w-full overflow-y-auto overflow-x-hidden p-4 md:inset-0"
>
    <div class="relative mx-auto max-h-full w-full {{ $maxWidth }}">
        <div class="relative rounded-lg border border-slate-200 bg-white shadow-xl">
            <button type="button" data-modal-hide="{{ $name }}" aria-label="بستن" class="absolute end-3 top-3 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-slate-400 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-primary/20">
                <x-heroicon-o-x-mark class="h-5 w-5" />
            </button>
            {{ $slot }}
        </div>
    </div>
</div>
