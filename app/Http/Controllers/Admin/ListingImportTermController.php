<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\ListingImportTerm;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListingImportTermController extends Controller
{
    public function index()
    {
        $terms = ListingImportTerm::query()->with('attribute')->orderByDesc('id')->paginate(20);

        return view('admin.listing-imports.terms', ['terms' => $terms, 'term' => null, 'targets' => $this->targets()]);
    }

    public function store(Request $request)
    {
        ListingImportTerm::create($this->validateTerm($request));

        return redirect()->route('admin.listing-imports.terms.index')->with('status', 'اصطلاح ثبت شد.');
    }

    public function edit(ListingImportTerm $term)
    {
        return view('admin.listing-imports.terms', ['term' => $term, 'targets' => $this->targets()]);
    }

    public function update(Request $request, ListingImportTerm $term)
    {
        $term->update($this->validateTerm($request, $term));

        return redirect()->route('admin.listing-imports.terms.index')->with('status', 'اصطلاح ویرایش شد.');
    }

    public function destroy(ListingImportTerm $term)
    {
        $term->delete();

        return redirect()->route('admin.listing-imports.terms.index')->with('status', 'اصطلاح حذف شد.');
    }

    private function validateTerm(Request $request, ?ListingImportTerm $term = null): array
    {
        $validated = $request->validate([
            'emoji' => ['required', 'string'],
            'meaning' => ['required', 'string', 'max:255'],
            'target' => ['nullable', 'string', Rule::in(collect($this->targets())->pluck('options')->flatten()->all())],
        ], [
            'emoji.required' => 'متن یا ایموجی اصطلاح را وارد کنید.',
            'meaning.required' => 'معنی فارسی اصطلاح را وارد کنید.',
            'meaning.max' => 'معنی اصطلاح حداکثر ۲۵۵ نویسه می‌تواند داشته باشد.',
            'target.in' => 'یکی از گزینه‌های فعلی مرحله‌ساز را انتخاب کنید.',
        ]);

        $uniqueSource = Rule::unique(ListingImportTerm::class, 'source_hash');
        if ($term) {
            $uniqueSource->ignore($term);
        }
        validator(['emoji' => ListingImportTerm::sourceHash($validated['emoji'])], [
            'emoji' => [$uniqueSource],
        ], ['emoji.unique' => 'این اصطلاح قبلاً تعریف شده است.'])->validate();

        [$attributeId, $attributeValue] = isset($validated['target'])
            ? json_decode($validated['target'], true)
            : [null, null];

        return [
            'emoji' => $validated['emoji'],
            'meaning' => $validated['meaning'],
            'attribute_id' => $attributeId,
            'attribute_value' => $attributeValue,
        ];
    }

    private function targets(): array
    {
        $attributes = Attribute::query()->where('is_active', true)
            ->whereIn('type', ['select', 'multi_select', 'integer'])
            ->with('modelAttributes')->orderBy('sort_order')->get();

        $targets = [];
        foreach ($attributes as $attribute) {
            $options = $attribute->modelAttributes
                ->flatMap(fn ($modelAttribute) => $modelAttribute->custom_options ?? [])
                ->merge($attribute->optionsForModel())->map('strval')->unique();

            if ($options->isNotEmpty()) {
                $targets[] = [
                    'label' => $attribute->name,
                    'options' => $options->mapWithKeys(fn (string $option) => [
                        $option => json_encode([$attribute->id, $option], JSON_UNESCAPED_UNICODE),
                    ])->all(),
                ];
            }
        }

        return $targets;
    }
}
