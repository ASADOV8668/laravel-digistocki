<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttributeType;
use App\Http\Controllers\Controller;
use App\Models\Attribute as ListingAttribute;
use App\Models\PhoneModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttributeController extends Controller
{
    use Concerns\InteractsWithAdminPagination;

    public function index(Request $request)
    {
        $perPage = $this->adminPerPage($request);
        $perPageOptions = $this->adminPerPageOptions();
        $attributes = ListingAttribute::withCount('phoneModels')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        // Keep the full model list out of the page HTML. The visibility picker
        // loads models on demand from the AJAX endpoint below.
        $activeModelIds = PhoneModel::query()
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $attributes->getCollection()->each(function (ListingAttribute $attribute) use ($activeModelIds): void {
            $selectedModelIds = array_map('intval', $attribute->visibility_model_ids ?? []);
            $attribute->visible_model_count = match ($attribute->visibility_rule ?: 'all') {
                'include' => count(array_intersect($activeModelIds, $selectedModelIds)),
                'exclude' => count(array_diff($activeModelIds, $selectedModelIds)),
                default => count($activeModelIds),
            };
        });

        return view('admin.attributes.index', [
            'attributes' => $attributes,
            'types' => AttributeType::cases(),
            'perPageOptions' => $perPageOptions,
        ]);
    }

    public function models(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q'));
        $selectedIds = collect((array) $request->input('selected_ids', []))
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->values()
            ->all();

        return response()->json(PhoneModel::query()
            ->where('is_active', true)
            ->with('brand:id,name,name_en')
            ->when($query !== '' || $selectedIds !== [], function ($builder) use ($query, $selectedIds): void {
                $builder->where(function ($builder) use ($query, $selectedIds): void {
                    if ($query !== '') {
                        $builder->where('name', 'like', '%'.$query.'%')
                            ->orWhere('name_fa', 'like', '%'.$query.'%')
                            ->orWhere('name_en', 'like', '%'.$query.'%')
                            ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', '%'.$query.'%')->orWhere('name_en', 'like', '%'.$query.'%'));
                    }

                    if ($selectedIds !== []) {
                        $builder->orWhereIn('id', $selectedIds);
                    }
                });
            })
            ->orderBy('brand_id')
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'brand_id', 'name', 'name_fa', 'name_en'])
            ->map(fn (PhoneModel $model): array => [
                'id' => $model->id,
                'brand_id' => $model->brand_id,
                'label' => ($model->brand?->name ?: $model->brand?->name_en).' · '.($model->name_fa ?: $model->name),
                'search' => implode(' ', array_filter([$model->brand?->name, $model->brand?->name_en, $model->name, $model->name_fa, $model->name_en])),
            ])->values());
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $attribute = ListingAttribute::create($validated);
        $this->syncVisibilityModels($attribute);

        return back()->with('status', 'ویژگی جدید اضافه شد.');
    }

    public function update(Request $request, ListingAttribute $attribute)
    {
        $attribute->update($this->validated($request, $attribute));
        $this->syncVisibilityModels($attribute->refresh());

        return back()->with('status', 'ویژگی به‌روزرسانی شد.');
    }

    public function toggle(Request $request, ListingAttribute $attribute): RedirectResponse|JsonResponse
    {
        $attribute->update(['is_active' => ! $attribute->is_active]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'وضعیت ویژگی تغییر کرد.',
                'is_active' => $attribute->is_active,
                'status_label' => $attribute->is_active ? 'فعال' : 'غیرفعال',
            ]);
        }

        return back()->with('status', 'وضعیت ویژگی تغییر کرد.');
    }

    private function validated(Request $request, ?ListingAttribute $attribute = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(AttributeType::class)],
            'unit' => ['nullable', 'string', 'max:20'],
            'options' => ['nullable', 'string', Rule::requiredIf(fn () => in_array($request->input('type'), [AttributeType::Select->value, AttributeType::MultiSelect->value], true))],
            'is_filterable' => ['nullable', 'boolean'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'visibility_rule' => ['required', Rule::in(['all', 'include', 'exclude'])],
            'visibility_model_ids' => ['nullable', 'array'],
            'visibility_model_ids.*' => ['integer', 'distinct', 'exists:phone_models,id'],
        ]);
        $options = filled($data['options'] ?? null) ? collect(preg_split('/[,،\n]+/u', $data['options']))->map(fn ($option) => trim($option))->filter()->unique()->values()->all() : [];
        if (in_array($data['type'], [AttributeType::Select->value, AttributeType::MultiSelect->value], true) && $options === []) {
            throw ValidationException::withMessages(['options' => 'حداقل یک گزینه معتبر وارد کنید.']);
        }
        $data['options'] = $options ?: null;
        $data['is_filterable'] = $request->boolean('is_filterable');
        $data['is_required'] = $request->boolean('is_required');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['visibility_rule'] = $data['visibility_rule'] ?? 'all';
        $data['visibility_model_ids'] = $data['visibility_rule'] === 'all'
            ? []
            : collect($data['visibility_model_ids'] ?? [])->map(fn ($id): int => (int) $id)->unique()->values()->all();

        if ($data['visibility_rule'] !== 'all' && $data['visibility_model_ids'] === []) {
            throw ValidationException::withMessages(['visibility_model_ids' => 'برای این شرط حداقل یک مدل را انتخاب کنید.']);
        }

        return $data;
    }

    private function syncVisibilityModels(ListingAttribute $attribute): void
    {
        $modelQuery = PhoneModel::query()->where('is_active', true);

        if ($attribute->visibility_rule === 'include') {
            $modelQuery->whereIn('id', $attribute->visibility_model_ids ?? []);
        } elseif ($attribute->visibility_rule === 'exclude') {
            $modelQuery->whereNotIn('id', $attribute->visibility_model_ids ?? []);
        }

        $modelIds = $modelQuery->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $attribute->modelAttributes()->whereNotIn('phone_model_id', $modelIds)->delete();

        foreach ($modelIds as $modelId) {
            $attribute->modelAttributes()->updateOrCreate(
                ['phone_model_id' => $modelId],
                ['is_required' => $attribute->is_required, 'sort_order' => $attribute->sort_order],
            );
        }
    }
}
