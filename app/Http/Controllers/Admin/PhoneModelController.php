<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\PhoneModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PhoneModelController extends Controller
{
    public function index(Request $request)
    {
        $models = PhoneModel::with(['brand', 'attributes' => fn ($query) => $query->where('attributes.is_active', true)->orderBy('model_attributes.sort_order')])->withCount('listings')
            ->when($request->filled('brand_id'), fn ($query) => $query->where('brand_id', $request->integer('brand_id')))
            ->when($request->filled('q'), fn ($query) => $query->where(function ($query) use ($request) {
                $like = '%'.$request->string('q').'%';
                $query->where('name', 'like', $like)->orWhere('name_fa', 'like', $like)->orWhere('name_en', 'like', $like);
            }))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.phone-models.index', ['models' => $models, 'brands' => Brand::orderBy('name')->get(), 'attributes' => Attribute::where('is_active', true)->orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $model = PhoneModel::create([...$this->modelData($validated), 'slug' => $this->uniqueSlug($validated['brand_id'], $validated['name_en'] ?? $validated['name']), 'is_active' => true]);
        $this->syncAttributes($model, $validated['attribute_ids'] ?? [], $validated['required_attribute_ids'] ?? []);

        return back()->with('status', 'مدل جدید اضافه شد.');
    }

    public function update(Request $request, PhoneModel $phoneModel)
    {
        $validated = $this->validated($request);
        $phoneModel->update([...$this->modelData($validated), 'slug' => $this->uniqueSlug($validated['brand_id'], $validated['name_en'] ?? $validated['name'], $phoneModel)]);
        $phoneModel->modelAttributes()->delete();
        $this->syncAttributes($phoneModel, $validated['attribute_ids'] ?? [], $validated['required_attribute_ids'] ?? []);

        return back()->with('status', 'مدل و ویژگی‌های آن به‌روزرسانی شد.');
    }

    public function toggle(PhoneModel $phoneModel)
    {
        $phoneModel->update(['is_active' => ! $phoneModel->is_active]);

        return back()->with('status', 'وضعیت مدل تغییر کرد.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['brand_id' => ['required', 'exists:brands,id'], 'name' => ['required', 'string', 'max:100'], 'name_fa' => ['nullable', 'string', 'max:100'], 'name_en' => ['nullable', 'string', 'max:100'], 'release_year' => ['nullable', 'integer', 'between:2000,2100'], 'attribute_ids' => ['array'], 'attribute_ids.*' => ['integer', 'exists:attributes,id'], 'required_attribute_ids' => ['array'], 'required_attribute_ids.*' => ['integer', 'exists:attributes,id']]);
    }

    private function modelData(array $validated): array
    {
        return ['brand_id' => $validated['brand_id'], 'name' => $validated['name'], 'name_fa' => $validated['name_fa'] ?? $validated['name'], 'name_en' => $validated['name_en'] ?? $validated['name'], 'release_year' => $validated['release_year'] ?? null];
    }

    private function uniqueSlug(int|string $brandId, string $name, ?PhoneModel $except = null): string
    {
        $baseSlug = Str::slug($name) ?: 'model';
        $slug = $baseSlug;
        $suffix = 2;
        while (PhoneModel::where('brand_id', $brandId)->where('slug', $slug)->when($except, fn ($query) => $query->whereKeyNot($except->id))->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }

    private function syncAttributes(PhoneModel $model, array $attributeIds, array $requiredAttributeIds = []): void
    {
        $requiredAttributeIds = collect($requiredAttributeIds)->map(fn ($id) => (int) $id);
        $model->modelAttributes()->createMany(collect($attributeIds)->values()->map(fn ($id, $sort) => ['attribute_id' => $id, 'is_required' => $requiredAttributeIds->contains((int) $id), 'sort_order' => $sort])->all());
    }
}
