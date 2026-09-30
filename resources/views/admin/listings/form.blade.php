@php($editing = isset($listing) && $listing)
<x-admin-layout :title="$editing ? 'ویرایش آگهی' : 'افزودن آگهی'">
    <x-slot name="header"><div class="flex items-center gap-3"><a href="{{ route('admin.listings.index') }}" class="rounded-xl bg-slate-100 p-2 text-slate-500 transition hover:bg-primary-50 hover:text-primary"><x-heroicon-o-arrow-right class="h-5 w-5" /></a><div><p class="text-xs font-bold text-primary">مدیریت آگهی‌ها</p><h1 class="mt-1 text-xl font-black text-slate-900">{{ $editing ? 'ویرایش آگهی' : 'افزودن آگهی جدید' }}</h1></div></div></x-slot>
    <section x-data='adminListingForm(@js(url("/listings/models")), @js(url("/locations/provinces")), @js(route("admin.users.search")), @js(old("phone_model_id", $listing->phone_model_id ?? "")), @js($initialModelAttributes), @js(old("attributes", $initialValues)), @js(old("province_id", $listing->province_id ?? "")), @js(old("city_id", $listing->city_id ?? "")), @js(old("user_id", $listing->user_id ?? "")))' class="mx-auto max-w-5xl space-y-6">
        @if ($errors->any())<div class="rounded-2xl border border-error/20 bg-error/10 p-4 text-sm font-bold text-error"><ul class="space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ $editing ? route('admin.listings.update', $listing) : route('admin.listings.store') }}" enctype="multipart/form-data" class="admin-card space-y-7 p-6">
            @csrf @if($editing) @method('PUT') @endif
            <div><h2 class="text-base font-black text-slate-900">ثبت‌کننده و مدل گوشی</h2><p class="mt-1 text-xs text-slate-400">مدیر می‌تواند آگهی را برای هر کاربر ثبت یا ویرایش کند.</p></div>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="relative"><label class="mb-2 block text-sm font-bold text-slate-700">کاربر آگهی</label><input type="hidden" name="user_id" x-model="userId"><input x-model="userQuery" value="{{ old('user_search', $listing?->user?->name ?? '') }}" @input.debounce.300ms="searchUsers" @focus="userQuery.length >= 2 && searchUsers()" required autocomplete="off" placeholder="جستجو با نام یا شماره موبایل" class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary"><div x-show="userResults.length" x-cloak class="absolute inset-x-0 top-full z-20 mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"><template x-for="user in userResults" :key="user.id"><button type="button" @click="selectUser(user)" class="flex w-full items-center justify-between border-b border-slate-100 px-4 py-3 text-right text-sm hover:bg-primary-50"><span class="font-bold text-slate-700" x-text="user.label"></span><span class="text-xs text-slate-400" x-text="user.mobile || user.email"></span></button></template></div><p x-show="selectedUserLabel" x-text="`کاربر انتخاب‌شده: ${selectedUserLabel}`" class="mt-2 text-xs font-bold text-primary"></p></div>
                <div><label class="mb-2 block text-sm font-bold text-slate-700">برند</label><select name="brand_id" required class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary"><option value="">انتخاب برند</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected(old('brand_id', $listing->brand_id ?? '') == $brand->id)>{{ $brand->name }} ({{ $brand->name_en }})</option>@endforeach</select></div>
                <div class="md:col-span-2"><label class="mb-2 block text-sm font-bold text-slate-700">مدل گوشی</label><select name="phone_model_id" x-model="modelId" @change="loadAttributes" required class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary"><option value="">انتخاب مدل</option>@foreach($brands as $brand)<optgroup label="{{ $brand->name }} / {{ $brand->name_en }}">@foreach($brand->phoneModels as $model)<option value="{{ $model->id }}">{{ $model->name_fa ?: $model->name }} — {{ $model->name_en ?: $model->name }}</option>@endforeach</optgroup>@endforeach</select></div>
            </div>
            <div class="border-t border-slate-100 pt-6"><h2 class="text-base font-black text-slate-900">مشخصات فنی</h2><div x-show="attributesLoading" class="mt-4 text-xs text-slate-400">در حال بارگذاری ویژگی‌ها...</div><div x-show="!attributesLoading && !attributes.length" class="mt-4 rounded-xl bg-warning/10 p-4 text-xs text-slate-600">برای این مدل ویژگی‌ای تعریف نشده است.</div><div x-show="!attributesLoading" class="mt-4 grid gap-4 md:grid-cols-2"><template x-for="attribute in attributes" :key="attribute.id"><div><label class="mb-2 block text-sm font-bold text-slate-700"><span x-text="attribute.name"></span><span x-show="attribute.unit" x-text="` (${attribute.unit})`" class="text-xs font-normal text-slate-400"></span><span x-show="attribute.is_required" class="text-error"> *</span></label><template x-if="attribute.type === 'boolean'"><label class="flex items-center gap-2 text-sm"><input type="checkbox" :name="`attributes[${attribute.id}]`" value="1" x-model="values[attribute.id]" class="rounded border-slate-300 text-primary focus:ring-primary"> دارد</label></template><template x-if="attribute.type === 'select'"><select :name="`attributes[${attribute.id}]`" x-model="values[attribute.id]" :required="attribute.is_required" class="w-full rounded-xl border-slate-200 text-sm"><option value="">انتخاب کنید</option><template x-for="option in attribute.options" :key="option"><option :value="option" x-text="option"></option></template></select></template><template x-if="attribute.type === 'multi_select'"><select multiple :name="`attributes[${attribute.id}][]`" x-model="values[attribute.id]" :required="attribute.is_required" class="min-h-24 w-full rounded-xl border-slate-200 text-sm"><template x-for="option in attribute.options" :key="option"><option :value="option" x-text="option"></option></template></select></template><template x-if="attribute.type === 'integer' || attribute.type === 'decimal'"><input :name="`attributes[${attribute.id}]`" x-model="values[attribute.id]" :required="attribute.is_required" type="number" :step="attribute.type === 'decimal' ? '0.01' : '1'" class="w-full rounded-xl border-slate-200 text-sm"></template><template x-if="attribute.type === 'string'"><input :name="`attributes[${attribute.id}]`" x-model="values[attribute.id]" :required="attribute.is_required" class="w-full rounded-xl border-slate-200 text-sm"></template></div></template></div></div>
            <div class="border-t border-slate-100 pt-6"><h2 class="text-base font-black text-slate-900">اطلاعات آگهی</h2><div class="mt-4 grid gap-4 md:grid-cols-2"><div class="md:col-span-2"><label class="mb-2 block text-sm font-bold text-slate-700">عنوان آگهی</label><input name="title" value="{{ old('title', $listing->title ?? '') }}" required class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary"></div><div x-data="{ priceOnRequest: @js((bool) old('price_on_request', $listing->price_on_request ?? false)) }"><label class="mb-2 block text-sm font-bold text-slate-700">قیمت (تومان)</label><input x-show="!priceOnRequest" type="number" min="0" name="price" value="{{ old('price', $listing->price ?? '') }}" :required="!priceOnRequest" class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary">@if ($allowContactPrice)<label class="mt-3 flex cursor-pointer items-center gap-2 text-sm font-bold text-slate-700"><input type="checkbox" name="price_on_request" value="1" x-model="priceOnRequest" @checked(old('price_on_request', $listing->price_on_request ?? false)) class="rounded border-slate-300 text-primary focus:ring-primary"> قیمت را اعلام نمی‌کنم؛ تماس بگیرید</label>@endif</div><label class="flex items-center gap-2 self-end rounded-xl bg-slate-50 p-3 text-sm font-bold text-slate-700"><input type="checkbox" name="is_negotiable" value="1" @checked(old('is_negotiable', $listing->is_negotiable ?? false)) class="rounded border-slate-300 text-primary focus:ring-primary"> قیمت قابل مذاکره است</label><div><label class="mb-2 block text-sm font-bold text-slate-700">وضعیت آگهی</label><select name="status" @disabled(!$editing) class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary"><option value="pending" @selected(old('status', $listing->status->value ?? 'pending') === 'pending')>در انتظار بررسی</option><option value="approved" @selected(old('status', $listing->status->value ?? '') === 'approved')>منتشرشده</option><option value="rejected" @selected(old('status', $listing->status->value ?? '') === 'rejected')>ردشده</option><option value="sold" @selected(old('status', $listing->status->value ?? '') === 'sold')>فروخته‌شده</option><option value="expired" @selected(old('status', $listing->status->value ?? '') === 'expired')>منقضی‌شده</option></select></div><div><label class="mb-2 block text-sm font-bold text-slate-700">استان</label><select name="province_id" x-model="provinceId" @change="loadCities" class="w-full rounded-xl border-slate-200 text-sm"><option value="">انتخاب استان</option>@foreach($provinces as $province)<option value="{{ $province->id }}">{{ $province->name }}</option>@endforeach</select></div><div><label class="mb-2 block text-sm font-bold text-slate-700">شهر</label><select name="city_id" x-model="cityId" :disabled="!provinceId || citiesLoading" class="w-full rounded-xl border-slate-200 text-sm"><option value="">انتخاب شهر</option><template x-for="city in cities" :key="city.id"><option :value="city.id" x-text="city.name"></option></template></select></div><div class="md:col-span-2"><label class="mb-2 block text-sm font-bold text-slate-700">توضیحات</label><textarea name="description" rows="5" class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary">{{ old('description', $listing->description ?? '') }}</textarea></div></div></div>
            <div class="border-t border-slate-100 pt-6"><h2 class="text-base font-black text-slate-900">تصاویر</h2><p class="mt-1 text-xs text-slate-400">حداکثر ۸ تصویر، هر تصویر {{ $maxImageUploadMb }} مگابایت.</p><div x-data='imagePicker(@js($maxImageUploadMb), {{ $editing ? max(0, 8 - $listing->images->count()) : 8 }})' class="mt-4"><label class="flex cursor-pointer items-center justify-center rounded-2xl border-2 border-dashed border-primary/20 bg-primary-50/40 p-6 text-sm font-bold text-primary"><x-heroicon-o-photo class="ml-2 h-6 w-6" />انتخاب تصاویر جدید<input @change="select($event)" type="file" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp" class="sr-only"></label><div x-show="previews.length" x-cloak class="mt-3 grid grid-cols-4 gap-2"><template x-for="preview in previews" :key="preview.url"><img :src="preview.url" :alt="preview.name" class="h-20 w-full rounded-xl object-cover"></template></div></div></div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end"><a href="{{ route('admin.listings.index') }}" class="rounded-xl bg-slate-100 px-5 py-3 text-center text-sm font-bold text-slate-600">انصراف</a><button class="rounded-xl bg-primary px-5 py-3 text-sm font-black text-white transition hover:bg-primary/90">{{ $editing ? 'ذخیره تغییرات' : 'ثبت آگهی' }}</button></div>
        </form>
        @if($editing && $listing->images->isNotEmpty())
            <div class="admin-card p-6"><h2 class="text-base font-black text-slate-900">تصاویر فعلی</h2><div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">@foreach($listing->images as $image)<div class="relative overflow-hidden rounded-2xl bg-slate-100"><img src="{{ asset('storage/'.$image->path) }}" alt="{{ $listing->title }}" class="h-32 w-full object-cover"><div class="absolute inset-x-2 bottom-2 flex items-center justify-between gap-2"><span class="rounded-full bg-black/60 px-2 py-1 text-[10px] font-bold text-white">{{ $image->is_primary ? 'تصویر اصلی' : 'تصویر' }}</span><form method="POST" action="{{ route('admin.listings.images.destroy', [$listing, $image]) }}" onsubmit="return confirm('این تصویر حذف شود؟')">@csrf @method('DELETE')<button class="rounded-lg bg-error px-2 py-1 text-[10px] font-bold text-white">حذف</button></form></div></div>@endforeach</div></div>
        @endif
    </section>
    <script>
        window.adminListingForm = (attributesEndpoint, citiesEndpoint, usersEndpoint, initialModelId = '', initialAttributes = [], initialValues = {}, initialProvinceId = '', initialCityId = '', initialUserId = '') => ({
            attributesEndpoint, citiesEndpoint, usersEndpoint, modelId: initialModelId, attributes: initialAttributes, values: initialValues || {}, attributesLoading: false, provinceId: initialProvinceId, cityId: initialCityId, cities: [], citiesLoading: false, userId: initialUserId, userQuery: @js($listing?->user?->name ?? ''), selectedUserLabel: @js($listing?->user?->name ?? ''), userResults: [], attributesController: null, citiesController: null, usersController: null,
            init() { if (this.provinceId) this.loadCities(false); },
            async loadAttributes() {
                this.attributesController?.abort();
                this.attributesController = null;
                if (!this.modelId) { this.attributes = []; this.attributesLoading = false; return; }
                const controller = new AbortController();
                this.attributesController = controller;
                this.attributesLoading = true;
                try {
                    const response = await fetch(`${this.attributesEndpoint}/${this.modelId}/attributes?all=1`, { signal: controller.signal });
                    if (!response.ok) throw new Error('Attribute request failed');
                    this.attributes = await response.json();
                } catch (error) {
                    if (error.name !== 'AbortError') this.attributes = [];
                } finally {
                    if (this.attributesController === controller) { this.attributesController = null; this.attributesLoading = false; }
                }
            },
            async loadCities(resetCity = true) {
                this.citiesController?.abort();
                this.citiesController = null;
                if (resetCity) this.cityId = '';
                this.cities = [];
                if (!this.provinceId) { this.citiesLoading = false; return; }
                const controller = new AbortController();
                this.citiesController = controller;
                this.citiesLoading = true;
                try {
                    const response = await fetch(`${this.citiesEndpoint}/${this.provinceId}/cities`, { signal: controller.signal });
                    if (!response.ok) throw new Error('City request failed');
                    this.cities = await response.json();
                } catch (error) {
                    if (error.name !== 'AbortError') this.cities = [];
                } finally {
                    if (this.citiesController === controller) { this.citiesController = null; this.citiesLoading = false; }
                }
            },
            async searchUsers() {
                this.usersController?.abort();
                this.usersController = null;
                if (this.userQuery.trim().length < 2) { this.userResults = []; return; }
                const controller = new AbortController();
                this.usersController = controller;
                try {
                    const response = await fetch(`${this.usersEndpoint}?q=${encodeURIComponent(this.userQuery)}`, { signal: controller.signal });
                    if (!response.ok) throw new Error('User request failed');
                    this.userResults = await response.json();
                } catch (error) {
                    if (error.name !== 'AbortError') this.userResults = [];
                } finally {
                    if (this.usersController === controller) this.usersController = null;
                }
            },
            selectUser(user) { this.userId = user.id; this.userQuery = user.label; this.selectedUserLabel = `${user.label} · ${user.mobile || user.email || ''}`; this.userResults = []; },
        });
    </script>
</x-admin-layout>
