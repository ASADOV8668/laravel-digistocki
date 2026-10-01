@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-2 block text-sm font-medium text-slate-700']) }}>
    {{ $value ?? $slot }}
</label>
