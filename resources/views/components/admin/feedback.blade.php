@if (session('status'))
    <div class="flex items-center gap-2 rounded-2xl border border-success/15 bg-success/10 px-4 py-3 text-sm font-bold text-success" role="status"><x-heroicon-o-check-circle class="h-5 w-5 shrink-0" />{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="flex items-start gap-2 rounded-2xl border border-error/15 bg-error/10 px-4 py-3 text-sm font-bold text-error" role="alert"><x-heroicon-o-exclamation-triangle class="h-5 w-5 shrink-0" /><span>{{ $errors->first() }}</span></div>
@endif
