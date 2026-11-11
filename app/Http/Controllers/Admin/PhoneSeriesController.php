<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\PhoneSeries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PhoneSeriesController extends Controller
{
    use Concerns\InteractsWithAdminPagination;

    public function index(Request $request)
    {
        $perPage = $this->adminPerPage($request);
        $perPageOptions = $this->adminPerPageOptions();
        $series = PhoneSeries::query()
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
            'brands' => Brand::query()->orderBy('name')->get(),
            'perPageOptions' => $perPageOptions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        PhoneSeries::create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['brand_id'], $validated['name_en'] ?: $validated['name']),
            'is_active' => true,
        ]);

        return back()->with('status', 'سری جدید اضافه شد.');
    }

    public function update(Request $request, PhoneSeries $phoneSeries): RedirectResponse
    {
        $validated = $this->validated($request);
        $phoneSeries->update([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['brand_id'], $validated['name_en'] ?: $validated['name'], $phoneSeries),
        ]);

        return back()->with('status', 'سری به‌روزرسانی شد.');
    }

    public function toggle(PhoneSeries $phoneSeries): RedirectResponse
    {
        $phoneSeries->update(['is_active' => ! $phoneSeries->is_active]);

        return back()->with('status', 'وضعیت سری تغییر کرد.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:100'],
            'name_fa' => ['nullable', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
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
