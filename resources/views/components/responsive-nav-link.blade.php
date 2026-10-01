@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-lg bg-primary-50 px-4 py-2 text-start text-base font-medium text-primary focus:outline-none focus:ring-4 focus:ring-primary-100 transition'
            : 'block w-full rounded-lg px-4 py-2 text-start text-base font-medium text-slate-600 hover:bg-slate-100 hover:text-neutral focus:outline-none focus:ring-4 focus:ring-slate-100 transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
