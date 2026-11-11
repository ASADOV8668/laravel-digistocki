<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Attribute;
use App\Models\BrandListingWizardStep;
use App\Models\PhoneModel;
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
                'can_post_listings' => $validated['can_post_listings'] ?? true,
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
                'can_post_listings' => array_key_exists('can_post_listings', $validated)
                    ? (bool) $validated['can_post_listings']
                    : (bool) $brand->can_post_listings,
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

    public function togglePosting(Brand $brand): RedirectResponse
    {
        $brand->update(['can_post_listings' => ! $brand->can_post_listings]);

        return back()->with('status', 'وضعیت ثبت آگهی برای برند تغییر کرد.');
    }

    public function wizardSteps(Brand $brand)
    {
        return view('admin.brands.wizard-steps', [
            'brand' => $brand->load(['wizardSteps.attribute']),
            'attributes' => Attribute::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'models' => PhoneModel::query()->where('brand_id', $brand->id)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'name_fa', 'name_en']),
        ]);
    }

    public function storeWizardStep(Request $request, Brand $brand): RedirectResponse
    {
        $validated = $request->validate([
            'step_type' => ['required', 'in:series,model,attribute'],
            'attribute_id' => ['nullable', 'integer', 'exists:attributes,id'],
            'display_type' => ['required', 'in:buttons,swiper'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999'],
        ], [
            'sort_order.required' => 'اولویت مرحله را وارد کنید.',
        ]);

        if ($validated['step_type'] === 'attribute' && empty($validated['attribute_id'])) {
            return back()->withInput()->withErrors(['attribute_id' => 'برای مرحله ویژگی، انتخاب ویژگی الزامی است.']);
        }

        if ($validated['step_type'] !== 'attribute') {
            $validated['attribute_id'] = null;
        }

        if ($validated['step_type'] === 'attribute' && BrandListingWizardStep::query()->where('brand_id', $brand->id)->where('step_type', 'attribute')->where('attribute_id', $validated['attribute_id'])->exists()) {
            return back()->withInput()->withErrors(['attribute_id' => 'این ویژگی قبلاً در مرحله‌ساز برند اضافه شده است.']);
        }

        $brand->wizardSteps()->create([
            ...$validated,
            'is_required' => (bool) ($validated['is_required'] ?? true),
        ]);

        return back()->with('status', 'مرحله به مرحله‌ساز برند اضافه شد.');
    }

    public function updateWizardStep(Request $request, Brand $brand, BrandListingWizardStep $wizardStep): RedirectResponse
    {
        abort_unless($wizardStep->brand_id === $brand->id, 404);
        $validated = $request->validate([
            'display_type' => ['required', 'in:buttons,swiper'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:999'],
        ]);
        $wizardStep->update([
            ...$validated,
            'is_required' => (bool) ($validated['is_required'] ?? false),
        ]);

        return back()->with('status', 'مرحله به‌روزرسانی شد.');
    }

    public function destroyWizardStep(Brand $brand, BrandListingWizardStep $wizardStep): RedirectResponse
    {
        abort_unless($wizardStep->brand_id === $brand->id, 404);
        $wizardStep->delete();

        return back()->with('status', 'مرحله حذف شد.');
    }

    private function validated(Request $request, SystemOptions $options): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'can_post_listings' => ['nullable', 'boolean'],
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
