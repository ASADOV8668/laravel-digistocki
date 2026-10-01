@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full rounded-lg border border-slate-300 bg-slate-50 p-2.5 text-sm text-neutral shadow-sm transition focus:border-primary focus:ring-primary disabled:cursor-not-allowed disabled:opacity-50']) }}>
