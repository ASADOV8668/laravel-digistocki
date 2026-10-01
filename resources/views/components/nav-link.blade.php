@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-lg bg-primary-50 px-3 py-2 text-sm font-medium text-primary focus:outline-none focus:ring-4 focus:ring-primary-100 transition'
            : 'inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-neutral focus:outline-none focus:ring-4 focus:ring-slate-100 transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
