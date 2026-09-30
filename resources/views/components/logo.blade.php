@php($siteTitle = app(\App\Services\SystemOptions::class)->get('site_title'))
<a {{ $attributes->merge(['href' => url('/')]) }} class="inline-flex items-center gap-2"><img src="{{ asset('images/logo.png') }}" alt="{{ $siteTitle }}" class="h-10 w-10" /><span class="text-lg font-black tracking-tight text-neutral">{{ $siteTitle }}</span></a>
