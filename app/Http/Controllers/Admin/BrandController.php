<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    use Concerns\InteractsWithAdminPagination;

    public function index(Request $request)
    {
        $perPage = $this->adminPerPage($request);
        $perPageOptions = $this->adminPerPageOptions();
        $brands = Brand::withCount('phoneModels')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%')->orWhere('name_en', 'like', '%'.$request->string('q').'%'))
            ->orderBy('name')->paginate($perPage)->withQueryString();

        return view('admin.brands.index', compact('brands', 'perPageOptions'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        Brand::create([...$validated, 'slug' => $this->uniqueSlug($validated['name_en'] ?? $validated['name']), 'is_active' => true]);

        return back()->with('status', 'برند جدید اضافه شد.');
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $this->validated($request);
        $brand->update([...$validated, 'slug' => $this->uniqueSlug($validated['name_en'] ?? $validated['name'], $brand)]);

        return back()->with('status', 'برند به‌روزرسانی شد.');
    }

    public function toggle(Request $request, Brand $brand): RedirectResponse|JsonResponse
    {
        $brand->update(['is_active' => ! $brand->is_active]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'وضعیت برند تغییر کرد.',
                'is_active' => $brand->is_active,
                'status_label' => $brand->is_active ? 'فعال' : 'غیرفعال',
            ]);
        }

        return back()->with('status', 'وضعیت برند تغییر کرد.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:100'], 'name_en' => ['nullable', 'string', 'max:100']]);
    }

    private function uniqueSlug(string $name, ?Brand $except = null): string
    {
        $baseSlug = Str::slug($name) ?: 'brand';
        $slug = $baseSlug;
        $suffix = 2;
        while (Brand::where('slug', $slug)->when($except, fn ($query) => $query->whereKeyNot($except->id))->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
