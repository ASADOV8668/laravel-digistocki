<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\PhoneModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PhoneModelController extends Controller
{
    public function index(Request $request)
    {
        $models = PhoneModel::with('brand')->withCount('listings')->when($request->filled('brand_id'), fn ($query) => $query->where('brand_id', $request->integer('brand_id')))->when($request->filled('q'), fn ($query) => $query->where(function ($query) use ($request) {
            $like = '%'.$request->string('q').'%';
            $query->where('name', 'like', $like)->orWhere('name_fa', 'like', $like)->orWhere('name_en', 'like', $like);
        }))->orderBy('name')->paginate(20)->withQueryString();
        return view('admin.phone-models.index', ['models' => $models, 'brands' => Brand::orderBy('name')->get(), 'attributes' => Attribute::where('is_active', true)->orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['brand_id' => ['required', 'exists:brands,id'], 'name' => ['required', 'string', 'max:100'], 'name_fa' => ['nullable', 'string', 'max:100'], 'name_en' => ['nullable', 'string', 'max:100'], 'release_year' => ['nullable', 'integer', 'between:2000,2100'], 'attribute_ids' => ['array'], 'attribute_ids.*' => ['integer', 'exists:attributes,id']]);
        $baseSlug = Str::slug($validated['name_en'] ?? $validated['name']) ?: 'model';
        $slug = $baseSlug;
        $suffix = 2;
        while (PhoneModel::where('brand_id', $validated['brand_id'])->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $model = PhoneModel::create(['brand_id' => $validated['brand_id'], 'name' => $validated['name'], 'name_fa' => $validated['name_fa'] ?? $validated['name'], 'name_en' => $validated['name_en'] ?? $validated['name'], 'slug' => $slug, 'release_year' => $validated['release_year'] ?? null, 'is_active' => true]);
        $model->modelAttributes()->createMany(collect($validated['attribute_ids'] ?? [])->map(fn ($id, $sort) => ['attribute_id' => $id, 'is_required' => false, 'sort_order' => $sort])->values()->all());
        return back()->with('status', 'مدل جدید اضافه شد.');
    }

    public function toggle(PhoneModel $phoneModel)
    {
        $phoneModel->update(['is_active' => ! $phoneModel->is_active]);
        return back()->with('status', 'وضعیت مدل تغییر کرد.');
    }
}
