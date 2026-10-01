@props(['variant' => 'primary', 'type' => 'button'])
@php($classes = match ($variant) {'secondary' => 'bg-secondary text-white hover:bg-secondary-600 focus:ring-secondary/30', 'ghost' => 'bg-transparent text-neutral hover:bg-slate-100 focus:ring-slate-200', default => 'bg-primary text-white hover:bg-primary-600 focus:ring-primary/30'})
<button type="{{ $type }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-center text-sm font-medium transition focus:outline-none focus:ring-4 {$classes}"]) }}>{{ $slot }}</button>
