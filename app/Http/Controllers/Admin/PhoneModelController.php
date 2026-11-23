<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttributeType;
use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\PhoneModel;
use App\Models\PhoneSeries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PhoneModelController extends Controller
{
    use Concerns\InteractsWithAdminPagination;

    public function index(Request $request)
    {
        $perPage = $this->adminPerPage($request);
        $perPageOptions = $this->adminPerPageOptions();
        $deviceType = $request->query('device_type');
        abort_if($deviceType !== null && (! is_string($deviceType) || ! isset(PhoneModel::DEVICE_TYPES[$deviceType])), 422);
        $models = PhoneModel::with(['brand', 'modelAttributes', 'attributes' => fn ($query) => $query->where('attributes.is_active', true)->orderBy('model_attributes.sort_order')])->withCount('listings')
            ->when($deviceType, fn ($query) => $query->where('device_type', $deviceType))
            ->when($request->filled('brand_id'), fn ($query) => $query->where('brand_id', $request->integer('brand_id')))
            ->when($request->filled('q'), fn ($query) => $query->where(function ($query) use ($request) {
                $like = '%'.$request->string('q').'%';
                $query->where('name', 'like', $like)->orWhere('name_fa', 'like', $like)->orWhere('name_en', 'like', $like);
            }))
            ->orderBy('name')->paginate($perPage)->withQueryString();

        return view('admin.phone-models.index', ['models' => $models, 'deviceType' => $deviceType, 'brands' => Brand::orderBy('name')->get(), 'series' => PhoneSeries::query()->with('brand')->where('is_active', true)->orderBy('brand_id')->orderBy('sort_order')->get(), 'attributes' => Attribute::where('is_active', true)->orderBy('sort_order')->get(), 'perPageOptions' => $perPageOptions]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        DB::transaction(function () use ($validated): void {
            $model = PhoneModel::create([...$this->modelData($validated), 'slug' => $this->uniqueSlug($validated['brand_id'], $validated['name_en'] ?? $validated['name']), 'is_active' => true]);
            $this->syncAttributes($model, $validated);
        });

        return back()->with('status', 'مدل جدید اضافه شد.');
    }

    public function update(Request $request, PhoneModel $phoneModel)
    {
        $validated = $this->validated($request);
        DB::transaction(function () use ($phoneModel, $validated): void {
            $phoneModel = PhoneModel::query()->lockForUpdate()->findOrFail($phoneModel->id);
            $phoneModel->update([...$this->modelData($validated), 'slug' => $this->uniqueSlug($validated['brand_id'], $validated['name_en'] ?? $validated['name'], $phoneModel)]);
            $this->syncAttributes($phoneModel, $validated);
        });

        return back()->with('status', 'مدل و ویژگی‌های آن به‌روزرسانی شد.');
    }

    public function toggle(Request $request, PhoneModel $phoneModel): RedirectResponse|JsonResponse
    {
        $phoneModel->update(['is_active' => ! $phoneModel->is_active]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'وضعیت مدل تغییر کرد.',
                'is_active' => $phoneModel->is_active,
                'status_label' => $phoneModel->is_active ? 'فعال' : 'غیرفعال',
            ]);
        }

        return back()->with('status', 'وضعیت مدل تغییر کرد.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'brand_id' => ['required', 'exists:brands,id'],
            'device_type' => ['sometimes', 'required', Rule::in(array_keys(PhoneModel::DEVICE_TYPES))],
            'phone_series_id' => ['nullable', 'integer', Rule::exists('phone_series', 'id')->where(fn ($query) => $query->where('brand_id', $request->integer('brand_id')))],
            'name' => ['required', 'string', 'max:100'],
            'name_fa' => ['nullable', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'release_year' => ['nullable', 'integer', 'between:2000,2100'],
            'attribute_ids' => ['array'],
            'attribute_ids.*' => ['integer', 'distinct', 'exists:attributes,id'],
            'required_attribute_ids' => ['array'],
            'required_attribute_ids.*' => ['integer', 'exists:attributes,id'],
            'attribute_options' => ['array'],
            'attribute_options.*' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function modelData(array $validated): array
    {
        return array_intersect_key($validated, ['device_type' => true]) + ['brand_id' => $validated['brand_id'], 'phone_series_id' => $validated['phone_series_id'] ?? null, 'name' => $validated['name'], 'name_fa' => $validated['name_fa'] ?? $validated['name'], 'name_en' => $validated['name_en'] ?? $validated['name'], 'release_year' => $validated['release_year'] ?? null];
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

    private function syncAttributes(PhoneModel $model, array $validated): void
    {
        $attributeIds = collect($validated['attribute_ids'] ?? [])->map(fn ($id) => (int) $id)->values();
        $requiredIds = array_map('intval', $validated['required_attribute_ids'] ?? []);
        $optionInputs = $validated['attribute_options'] ?? [];
        $attributes = Attribute::whereKey($attributeIds->all())->get()->keyBy('id');

        // The editor lists active features. Keep the existing links for inactive features.
        $model->modelAttributes()->whereNotIn('attribute_id', $attributeIds->all())
            ->whereHas('attribute', fn ($query) => $query->where('is_active', true))->delete();

        foreach ($attributeIds as $sort => $id) {
            $attribute = $attributes->get($id);
            $settings = ['is_required' => in_array($id, $requiredIds, true), 'sort_order' => $sort];
            if ($attribute->supportsCustomOptions() && array_key_exists($id, $optionInputs)) {
                $options = collect(preg_split('/[,،\r\n]+/u', (string) $optionInputs[$id]))
                    ->map(fn (string $option): string => trim($option))
                    ->filter(fn (string $option): bool => $option !== '')
                    ->unique()->values()->all();
                if (count($options) > 80 || collect($options)->contains(fn (string $option): bool => mb_strlen($option) > 100
                    || ($attribute->type === AttributeType::Integer && filter_var($option, FILTER_VALIDATE_INT) === false)
                    || ($attribute->type === AttributeType::Decimal && ! preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/D', $option)))) {
                    throw ValidationException::withMessages(['attribute_options.'.$id => 'گزینه‌های «'.$attribute->name.'» را با فرمت متناسب با نوع ویژگی وارد کنید.']);
                }
                $settings['custom_options'] = $options === [] ? null : $options;
            }
            $model->modelAttributes()->updateOrCreate(['attribute_id' => $id], $settings);
        }
    }
}
