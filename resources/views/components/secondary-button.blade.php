<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-slate-100']) }}>
    {{ $slot }}
</button>
