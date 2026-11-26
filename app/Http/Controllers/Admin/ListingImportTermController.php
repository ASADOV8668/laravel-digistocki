<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\ListingImportTerm;
use App\Support\PersianSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

    public function storeBulk(Request $request)
    {
        $targets = collect($this->targets());
        $validated = $request->validate([
            'bulk_mode' => ['required', Rule::in(['colors', 'terms'])],
            'bulk_attribute' => ['nullable', 'integer', Rule::in($targets->pluck('id')->all())],
            'bulk_lines' => ['required', 'string', 'max:200000'],
        ], [
            'bulk_lines.required' => 'لیست را وارد کنید.',
            'bulk_lines.max' => 'حجم لیست بیش از حد مجاز است.',
        ]);

        $colors = $validated['bulk_mode'] === 'colors';
        $groups = [];
        foreach (preg_split('/\R/u', $validated['bulk_lines']) as $index => $line) {
            if (trim($line) === '') {
                continue;
            }
            [$source, $value] = array_map('trim', explode('=', $line, 2) + ['', '']);
            if ($source === '' || $value === '' || str_contains($value, '=') || (! $colors && mb_strlen($value) > 255)) {
                throw ValidationException::withMessages([
                    'bulk_lines' => 'ردیف '.($index + 1).' را با قالب اصطلاح=مقدار و مقدار معتبر وارد کنید.',
                ]);
            }
            $hash = ListingImportTerm::sourceHash($source);
            $groups[$hash] ??= ['source' => $source, 'values' => []];
            $groups[$hash]['values'][] = $value;
        }

        $attributeId = (int) ($validated['bulk_attribute'] ?? 0);
        if ($attributeId) {
            $targets = $targets->where('id', $attributeId);
        }

        $unmapped = DB::transaction(function () use ($groups, $colors, $targets, $attributeId): int {
            $unmapped = 0;
            foreach ($groups as $hash => $group) {
                $term = ListingImportTerm::query()->where('source_hash', $hash)->lockForUpdate()->first()
                    ?? new ListingImportTerm(['emoji' => $group['source'], 'meaning' => 'رنگ']);
                $values = collect($group['values'])->unique(fn (string $value): string => mb_strtolower($value))->values();
                if ($colors) {
                    $term->color_candidates = collect($term->color_candidates ?? [])->merge($values)
                        ->unique(fn (string $value): string => mb_strtolower($value))->values()->all();
                } else {
                    if ($values->count() !== 1) {
                        throw ValidationException::withMessages([
                            'bulk_lines' => 'برای اصطلاح «'.$group['source'].'» یک معنی وارد کنید. چند مقدار فقط در حالت رنگ پشتیبانی می‌شود.',
                        ]);
                    }
                    $term->meaning = $values->first();
                    $matches = $targets->flatMap(function (array $target) use ($term): array {
                        $key = $this->optionKey($term->meaning, $target['activation']);

                        return array_values(array_filter($target['options'], fn (string $option): bool => $this->optionKey(json_decode($option, true)[1], $target['activation']) === $key));
                    })->unique()->values();
                    if ($matches->count() === 1) {
                        [$term->attribute_id, $term->attribute_value] = json_decode($matches->first(), true);
                    } elseif ($attributeId) {
                        throw ValidationException::withMessages([
                            'bulk_lines' => 'برای «'.$group['source'].'» گزینه یکتایی در ویژگی انتخاب‌شده پیدا نشد.',
                        ]);
                    } else {
                        $unmapped++;
                    }
                }
                $term->save();
            }

            return $unmapped;
        });

        $status = count($groups).' اصطلاح ثبت شد.';
        if ($unmapped > 0) {
            $status .= ' برای '.$unmapped.' اصطلاح گزینه یکتایی پیدا نشد؛ نگاشت آن‌ها را در ویرایش بررسی کنید.';
        }

        return redirect()->route('admin.listing-imports.terms.index')->with('status', $status);
    }

    private function optionKey(string $value, bool $activation): string
    {
        $value = mb_strtolower(PersianSearch::normalize($value));
        $key = preg_replace('/[^\pL\pN]+/u', '', $value) ?? $value;

        return $activation ? match ($key) {
            'notact', 'notactive', 'ناتاکتیو', 'فعالنشده', 'غیرفعال' => 'notactive',
            'active', 'act', 'اکتیو', 'فعال', 'فعالشده' => 'active',
            default => $key,
        } : $key;
    }

    public function edit(ListingImportTerm $term)
    {
        return view('admin.listing-imports.terms', ['term' => $term, 'targets' => $this->targets()]);
    }

    public function update(Request $request, ListingImportTerm $term)
    {
        $data = $this->validateTerm($request, $term);
        if ($request->exists('candidate_lines')) {
            $request->validate(['candidate_lines' => ['nullable', 'string', 'max:200000']]);
            $data['color_candidates'] = collect(preg_split('/\R/u', $request->string('candidate_lines')->toString()))
                ->map('trim')->filter()->unique(fn (string $color) => mb_strtolower($color))->values()->all() ?: null;
        }
        $term->update($data);

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
                    'id' => $attribute->id,
                    'label' => $attribute->name,
                    'activation' => $attribute->slug === 'activation_status' || str_contains($attribute->name, 'ACT/NOT'),
                    'options' => $options->mapWithKeys(fn (string $option) => [
                        $option => json_encode([$attribute->id, $option], JSON_UNESCAPED_UNICODE),
                    ])->all(),
                ];
            }
        }

        return $targets;
    }
}
