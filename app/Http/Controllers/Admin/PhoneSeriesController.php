<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\PhoneModel;
use App\Models\PhoneSeries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PhoneSeriesController extends Controller
{
    use Concerns\InteractsWithAdminPagination;

    public function index(Request $request)
    {
        $perPage = $this->adminPerPage($request);
        $perPageOptions = $this->adminPerPageOptions();
        $deviceType = $request->query('device_type', 'mobile');
        abort_unless(is_string($deviceType) && isset(PhoneModel::DEVICE_TYPES[$deviceType]), 422);
        $series = PhoneSeries::query()
            ->whereHas('brand', fn ($query) => $query->where('device_type', $deviceType))
            ->with('brand')
            ->withCount('phoneModels')
            ->when($request->integer('brand_id'), fn ($query, int $brandId) => $query->where('brand_id', $brandId))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('name_fa', 'like', $term)->orWhere('name_en', 'like', $term));
            })
            ->orderBy('brand_id')->orderBy('sort_order')->orderBy('name')
            ->paginate($perPage)->withQueryString();

        return view('admin.phone-series.index', [
            'series' => $series,
            'brands' => Brand::query()->where('device_type', $deviceType)->orderBy('name')->get(),
            'deviceType' => $deviceType,
            'perPageOptions' => $perPageOptions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        PhoneSeries::create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['brand_id'], $validated['name_en'] ?? $validated['name']),
            'is_active' => true,
        ]);

        return back()->with('status', 'سری جدید اضافه شد.');
    }

    public function update(Request $request, PhoneSeries $phoneSeries): RedirectResponse
    {
        $validated = $this->validated($request, $phoneSeries->brand->device_type);
        if ($phoneSeries->brand_id !== (int) $validated['brand_id'] && $phoneSeries->phoneModels()->exists()) {
            throw ValidationException::withMessages(['brand_id' => 'برای تغییر برند، ابتدا مدل‌های این سری را به سری دیگری منتقل کنید.']);
        }

        $phoneSeries->update([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['brand_id'], $validated['name_en'] ?? $validated['name'], $phoneSeries),
        ]);

        return back()->with('status', 'سری به‌روزرسانی شد.');
    }

    public function toggle(PhoneSeries $phoneSeries): RedirectResponse
    {
        $phoneSeries->update(['is_active' => ! $phoneSeries->is_active]);

        return back()->with('status', 'وضعیت سری تغییر کرد.');
    }

    private function validated(Request $request, ?string $deviceType = null): array
    {
        $deviceType ??= $request->validate([
            'device_type' => ['sometimes', 'required', Rule::in(array_keys(PhoneModel::DEVICE_TYPES))],
        ])['device_type'] ?? 'mobile';
        $validated = $request->validate([
            'brand_id' => ['required', 'integer', Rule::exists('brands', 'id')->where('device_type', $deviceType)],
            'name' => ['required', 'string', 'max:100'],
            'name_fa' => ['nullable', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $validated['sort_order'] ??= 0;

        return $validated;
    }

    private function uniqueSlug(int $brandId, string $name, ?PhoneSeries $except = null): string
    {
        $base = Str::slug($name) ?: 'series';
        $slug = $base;
        $suffix = 2;
        while (PhoneSeries::query()->where('brand_id', $brandId)->where('slug', $slug)->when($except, fn ($query) => $query->whereKeyNot($except->id))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
