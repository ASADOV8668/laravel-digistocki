@props(['paginator', 'route', 'perPageOptions'])

<div class="space-y-4">
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-100 bg-white px-4 py-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
        <p>
            نمایش
            <span class="font-black text-slate-800">{{ number_format($paginator->total()) }}</span>
            مورد در
            <span class="font-black text-slate-800">{{ number_format($paginator->lastPage()) }}</span>
            صفحه
        </p>
        <form method="GET" action="{{ route($route) }}" class="flex items-center gap-2">
            @foreach (request()->except(['page', 'per_page']) as $key => $value)
                @if (is_scalar($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label for="admin-per-page" class="font-bold text-slate-600">نمایش در صفحه</label>
            <select id="admin-per-page" name="per_page" onchange="this.form.submit()"
                class="rounded-xl border-slate-200 py-2 pl-8 pr-3 text-xs font-bold text-slate-700 focus:border-primary focus:ring-primary">
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}" @selected($paginator->perPage() === $option)>{{ number_format($option) }} مورد</option>
                @endforeach
            </select>
        </form>
    </div>

    {{ $paginator->links('vendor.pagination.flowbite') }}
</div>
