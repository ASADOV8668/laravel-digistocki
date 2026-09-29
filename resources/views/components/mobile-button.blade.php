@props(['variant' => 'primary', 'type' => 'button'])
@php($classes = match ($variant) {'secondary' => 'bg-secondary text-white hover:bg-secondary-600', 'ghost' => 'bg-transparent text-neutral hover:bg-slate-100', default => 'bg-primary text-white hover:bg-primary-600'})
<button type="{{ $type }}" {{ $attributes->merge(['class' => "mobile-button {$classes}"]) }}>{{ $slot }}</button>
