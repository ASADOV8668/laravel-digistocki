@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white dark:bg-slate-900'])

@php
    $alignmentClasses = match ($align) {
        'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
        'top' => 'origin-top',
        default => 'ltr:origin-top-right rtl:origin-top-left end-0',
    };

    $width = match ($width) {
        '48' => 'w-48',
        default => $width,
    };
@endphp

@php($dropdownId = 'flowbite-dropdown-' . uniqid())

<div class="relative">
    <div data-dropdown-toggle="{{ $dropdownId }}"
        data-dropdown-placement="{{ $align === 'left' ? 'bottom-start' : ($align === 'top' ? 'top' : 'bottom-end') }}">
        {{ $trigger }}
    </div>

    <div id="{{ $dropdownId }}"
        class="z-50 hidden {{ $width }} rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900 {{ $alignmentClasses }}"
        role="menu">
        <div class="rounded-lg p-1 {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
