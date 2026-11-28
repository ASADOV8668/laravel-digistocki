<div x-cloak x-show="$store.listingCompare" class="absolute left-2 top-12 flex flex-col items-start gap-1">
    <button type="button"
        @click.stop.prevent="$store.listingCompare.toggle(@js($listingId))"
        :aria-pressed="$store.listingCompare?.ids.includes(@js($listingId)) ? 'true' : 'false'"
        :aria-label="$store.listingCompare?.ids.includes(@js($listingId)) ? 'حذف از مقایسه' : 'افزودن به مقایسه'"
        :disabled="$store.listingCompare?.ids.length >= 3 && !$store.listingCompare.ids.includes(@js($listingId))"
        title="مقایسه"
        class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white/95 shadow-sm transition hover:text-primary focus:outline-none focus:ring-4 focus:ring-primary/20 disabled:opacity-50"
        :class="$store.listingCompare?.ids.includes(@js($listingId)) ? 'text-primary' : 'text-slate-500'">
        <x-heroicon-o-scale class="h-4 w-4" />
    </button>
    <a x-show="$store.listingCompare?.ids.length >= 2"
        :href="$store.listingCompare?.url(@js(route('listings.index')))"
        class="rounded-md bg-white/95 px-2 py-1 text-xs font-bold text-primary shadow-sm">مشاهدهٔ مقایسه</a>
</div>
