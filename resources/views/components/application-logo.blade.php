@php($siteTitle = app(\App\Services\SystemOptions::class)->get('site_title'))
<img src="{{ asset('images/logo.png') }}" alt="{{ $siteTitle }}" {{ $attributes->merge(['class' => 'h-9 w-9']) }} />
