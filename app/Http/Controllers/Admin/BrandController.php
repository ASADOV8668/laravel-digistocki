<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $brands = Brand::withCount('phoneModels')->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%')->orWhere('name_en', 'like', '%'.$request->string('q').'%'))->orderBy('name')->paginate(20)->withQueryString();
        return view('admin.brands.index', compact('brands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:100'], 'name_en' => ['nullable', 'string', 'max:100']]);
        $baseSlug = Str::slug($validated['name_en'] ?: $validated['name']);
        $slug = $baseSlug;
        $suffix = 2;
        while (Brand::where('slug', $slug)->exists()) $slug = $baseSlug.'-'.$suffix++;
        Brand::create([...$validated, 'slug' => $slug, 'is_active' => true]);
        return back()->with('status', 'برند جدید اضافه شد.');
    }

    public function toggle(Brand $brand)
    {
        $brand->update(['is_active' => ! $brand->is_active]);
        return back()->with('status', 'وضعیت برند تغییر کرد.');
    }
}
