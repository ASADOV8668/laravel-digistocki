@props(['active'])

<nav class="grid grid-cols-2 gap-2 rounded-2xl bg-slate-100 p-1 sm:grid-cols-4" aria-label="مدیریت کاتالوگ">
    @foreach (['brands' => ['label' => 'برندها', 'route' => 'admin.brands.index'], 'series' => ['label' => 'سری‌ها', 'route' => 'admin.phone-series.index'], 'models' => ['label' => 'مدل‌ها', 'route' => 'admin.phone-models.index'], 'attributes' => ['label' => 'ویژگی‌ها', 'route' => 'admin.attributes.index']] as $key => $item)
        <a href="{{ route($item['route']) }}"
            class="rounded-xl px-3 py-3 text-center text-xs transition hover:bg-white {{ $active === $key ? 'bg-white font-black text-accent shadow-sm' : 'font-bold text-slate-500' }}">{{ $item['label'] }}</a>
    @endforeach
</nav>
