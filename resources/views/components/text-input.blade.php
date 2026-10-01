@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'public-input']) }}>
