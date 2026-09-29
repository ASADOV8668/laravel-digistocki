<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttributeType;
use App\Http\Controllers\Controller;
use App\Models\Attribute as ListingAttribute;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $attributes = ListingAttribute::withCount('phoneModels')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.attributes.index', ['attributes' => $attributes, 'types' => AttributeType::cases()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        ListingAttribute::create($validated);
        return back()->with('status', 'ویژگی جدید اضافه شد.');
    }

    public function update(Request $request, ListingAttribute $attribute)
    {
        $attribute->update($this->validated($request, $attribute));
        return back()->with('status', 'ویژگی به‌روزرسانی شد.');
    }

    public function toggle(ListingAttribute $attribute)
    {
        $attribute->update(['is_active' => ! $attribute->is_active]);
        return back()->with('status', 'وضعیت ویژگی تغییر کرد.');
    }

    private function validated(Request $request, ?ListingAttribute $attribute = null): array
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'type' => ['required', Rule::enum(AttributeType::class)], 'unit' => ['nullable', 'string', 'max:20'], 'options' => ['nullable', 'string', Rule::requiredIf(fn () => in_array($request->input('type'), [AttributeType::Select->value, AttributeType::MultiSelect->value], true))], 'is_filterable' => ['nullable', 'boolean'], 'is_required' => ['nullable', 'boolean'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535']]);
        $data['options'] = filled($data['options'] ?? null) ? collect(preg_split('/[,،\n]+/u', $data['options']))->map(fn ($option) => trim($option))->filter()->unique()->values()->all() : null;
        $data['is_filterable'] = $request->boolean('is_filterable');
        $data['is_required'] = $request->boolean('is_required');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        return $data;
    }
}
