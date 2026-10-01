@if ($paginator->hasPages())
    <nav class="flex items-center justify-center" aria-label="صفحه‌بندی" dir="rtl">
        <ul class="inline-flex -space-x-px space-x-reverse text-sm">
            @if ($paginator->onFirstPage())
                <li>
                    <span class="ms-0 flex h-9 items-center justify-center rounded-s-lg border border-slate-200 bg-slate-100 px-3 font-medium text-slate-400" aria-disabled="true">قبلی</span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="ms-0 flex h-9 items-center justify-center rounded-s-lg border border-slate-200 bg-white px-3 font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary/30">قبلی</a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="flex h-9 items-center justify-center border-y border-slate-200 bg-white px-3 font-medium text-slate-500">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li aria-current="page"><span class="flex h-9 items-center justify-center border border-primary bg-primary px-3 font-medium text-white">{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}" class="flex h-9 items-center justify-center border-y border-slate-200 bg-white px-3 font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary/30">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" class="flex h-9 items-center justify-center rounded-e-lg border border-slate-200 bg-white px-3 font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary/30">بعدی</a></li>
            @else
                <li><span class="flex h-9 items-center justify-center rounded-e-lg border border-slate-200 bg-slate-100 px-3 font-medium text-slate-400" aria-disabled="true">بعدی</span></li>
            @endif
        </ul>
    </nav>
@endif
