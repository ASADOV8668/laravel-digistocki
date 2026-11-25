<x-app-layout title="مقایسهٔ آگهی‌ها">
    <section x-data class="mx-auto max-w-5xl space-y-4 px-4 py-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-black text-neutral">مقایسهٔ آگهی‌ها</h1>
            <div class="flex gap-3 text-sm font-bold text-primary">
                <button type="button" @click="$store.listingCompare.clear(); window.location.assign(@js(route('listings.index')))">پاک‌کردن انتخاب‌ها</button>
                <a href="{{ route('listings.index') }}">بازگشت به آگهی‌ها</a>
            </div>
        </div>
        @if ($listings->count() < 2)
            <p class="rounded-lg border border-slate-200 p-4 text-sm">برای مقایسه دست‌کم دو آگهی فعال انتخاب کنید.</p>
        @else
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full min-w-[32rem] text-right text-sm">
                    <thead>
                        <tr>
                            <th scope="col" class="p-3">ویژگی</th>
                            @foreach ($listings as $listing)
                                <th scope="col" class="p-3 align-top">
                                    <a href="{{ route('listings.show', $listing) }}" class="block">
                                        <img class="mb-2 h-24 w-full rounded-lg object-cover" src="{{ $listing->primaryImage?->displayUrl(true) ?: asset('images/listing-placeholder.svg') }}" alt="">
                                        {{ $listing->title }}
                                    </a>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr>
                            <th scope="row" class="p-3">قیمت</th>
                            @foreach ($listings as $listing)
                                <td class="p-3 font-black text-primary">{{ $listing->price_on_request || $listing->price === null ? 'تماس بگیرید' : \App\Support\PersianNumber::format($listing->price).' تومان' }}</td>
                            @endforeach
                        </tr>
                        @foreach (['storage' => 'حافظه', 'battery_health' => 'سلامت باتری', 'registration_status' => 'رجیستری', 'color' => 'رنگ', 'condition' => 'وضعیت دستگاه', 'city' => 'شهر'] as $slug => $label)
                            <tr>
                                <th scope="row" class="p-3">{{ $label }}</th>
                                @foreach ($listings as $listing)
                                    @php
                                        $value = $listing->attributeValues->first(fn ($entry) => $entry->attribute?->slug === $slug);
                                        $display = $value?->value_string ?? $value?->value_integer;
                                        if ($slug === 'registration_status' && $display === null) {
                                            $registered = $listing->attributeValues->first(fn ($entry) => $entry->attribute?->slug === 'registered')?->value_boolean;
                                            $display = \App\Support\RegistrationStatus::fromBoolean($registered);
                                        }
                                    @endphp
                                    <td class="p-3">
                                        {{ $slug === 'city' ? ($listing->city?->name ?? '—') : ($display === null ? '—' : \App\Support\PersianNumber::digits((string) $display).' '.($value?->attribute?->unit ?? '')) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-app-layout>
