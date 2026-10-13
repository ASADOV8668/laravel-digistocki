@props(['platform'])

@php
    $labels = [
        'instagram' => 'اینستاگرام',
        'telegram' => 'تلگرام',
        'whatsapp' => 'واتساپ',
        'bale' => 'بله',
        'rubika' => 'روبیکا',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" fill="none"
    xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ $labels[$platform] ?? 'شبکه اجتماعی' }}">
    @if ($platform === 'instagram')
        <rect x="3.25" y="3.25" width="17.5" height="17.5" rx="5" stroke="currentColor" stroke-width="1.8" />
        <circle cx="12" cy="12" r="4.1" stroke="currentColor" stroke-width="1.8" />
        <circle cx="17.6" cy="6.5" r="1.1" fill="currentColor" />
    @elseif ($platform === 'telegram')
        <path d="m21.2 4.2-3.1 15.1c-.2 1.1-.8 1.4-1.7.9l-4.8-3.5-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.9 8.9-8c.4-.3-.1-.5-.6-.2L6 13.3l-4.7-1.5c-1-.3-1-1 .2-1.5L19.8 3c.9-.3 1.7.2 1.4 1.2Z" fill="currentColor" />
    @elseif ($platform === 'whatsapp')
        <path d="M12 3.2a8.8 8.8 0 0 0-7.6 13.3L3.3 20.8l4.5-1.1A8.8 8.8 0 1 0 12 3.2Z" stroke="currentColor" stroke-width="1.8" />
        <path d="M8.5 8.6c.2-.4.5-.4.8-.4h.5c.2 0 .4.1.5.4l.7 1.7c.1.3.1.5-.1.7l-.5.7c.5 1 1.3 1.8 2.3 2.3l.7-.5c.2-.2.4-.2.7-.1l1.7.7c.3.1.4.3.4.5v.5c0 .3 0 .6-.4.8-.4.2-1.4.4-2.7-.1-1.4-.5-3.1-2-3.9-3.1-.8-1.1-1.5-2.6-1.5-3.5 0-.8.4-1.4.8-1.7Z" fill="currentColor" />
    @else
        <path d="M5 5.5h14a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-7l-4.5 3v-3H5a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
        <path d="M7.5 10.5h9M7.5 13.5h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
    @endif
</svg>
