<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\ImageService;
use App\Services\SystemOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
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

    public function store(Request $request, ImageService $imageService, SystemOptions $options): RedirectResponse
    {
        $validated = $this->validated($request, $options);
        $imagePath = null;

        try {
            if ($request->hasFile('featured_image')) {
                $imagePath = $imageService->store($request->file('featured_image'), 'brands', false)['path'];
            }

            Brand::create([
                ...$validated,
                'featured_image' => $imagePath,
                'slug' => $this->uniqueSlug($validated['name_en'] ?? $validated['name']),
                'is_active' => true,
            ]);
        } catch (\Throwable $exception) {
            $imageService->delete($imagePath);
            report($exception);

            return back()->withInput()->with('error', 'ثبت برند انجام نشد. لطفاً دوباره تلاش کنید.');
        }

        return back()->with('status', 'برند جدید اضافه شد.');
    }

    public function update(Request $request, Brand $brand, ImageService $imageService, SystemOptions $options): RedirectResponse
    {
        $validated = $this->validated($request, $options);
        $oldImagePath = $brand->featured_image;
        $newImagePath = $oldImagePath;

        try {
            if ($request->hasFile('featured_image')) {
                $newImagePath = $imageService->store($request->file('featured_image'), 'brands', false)['path'];
            } elseif ($request->boolean('remove_featured_image')) {
                $newImagePath = null;
            }

            $brand->update([
                ...$validated,
                'featured_image' => $newImagePath,
                'slug' => $this->uniqueSlug($validated['name_en'] ?? $validated['name'], $brand),
            ]);

            if ($newImagePath !== $oldImagePath && $oldImagePath && Str::startsWith($oldImagePath, 'brands/')) {
                Storage::disk('public')->delete($oldImagePath);
            }
        } catch (\Throwable $exception) {
            if ($newImagePath !== $oldImagePath) {
                $imageService->delete($newImagePath);
            }
            report($exception);

            return back()->withInput()->with('error', 'ویرایش برند انجام نشد. لطفاً دوباره تلاش کنید.');
        }

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

    private function validated(Request $request, SystemOptions $options): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'featured_image' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,heic,heif',
                'max:'.($options->maxImageUploadMb() * 1024),
            ],
            'remove_featured_image' => ['nullable', 'boolean'],
        ], [
            'featured_image.mimes' => 'تصویر شاخص برند باید jpg، jpeg، png، webp، heic یا heif باشد.',
            'featured_image.max' => 'حجم تصویر شاخص برند نباید بیشتر از '. $options->maxImageUploadMb().' مگابایت باشد.',
        ]);

        unset($validated['remove_featured_image']);

        return $validated;
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
