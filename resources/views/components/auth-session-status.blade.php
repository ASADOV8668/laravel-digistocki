@props(['status'])

@if ($status)
    <x-flowbite-alert type="success" {{ $attributes }}>{{ $status }}</x-flowbite-alert>
@endif
