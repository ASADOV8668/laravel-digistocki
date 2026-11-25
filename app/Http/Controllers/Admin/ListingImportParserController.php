<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListingImportParser;
use App\Services\ListingImport\Parsers\PatternParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ListingImportParserController extends Controller
{
    public function index(Request $request)
    {
        $parsers = ListingImportParser::query()->orderBy('title')->get();
        $selected = $request->filled('parser')
            ? $parsers->firstWhere('key', $request->string('parser')->toString())
            : $parsers->first();

        return view('admin/listing-imports/index', compact('parsers', 'selected'));
    }

    public function create(): View
    {
        return view('admin.listing-imports.form', ['parser' => new ListingImportParser(['is_active' => true])]);
    }

    public function edit(ListingImportParser $parser): View
    {
        return view('admin.listing-imports.form', compact('parser'));
    }

    public function store(Request $request): RedirectResponse
    {
        $parser = new ListingImportParser(['class_name' => PatternParser::class]);
        $parser->fill($this->validated($request, $parser))->save();

        return $this->saved($parser);
    }

    public function update(Request $request, ListingImportParser $parser): RedirectResponse
    {
        $parser->update($this->validated($request, $parser));

        return $this->saved($parser);
    }

    private function validated(Request $request, ListingImportParser $parser): array
    {
        $custom = $parser->class_name === PatternParser::class;
        $data = $request->validate([
            'key' => ['required', 'string', 'max:80', 'regex:/\A[a-z0-9][a-z0-9_-]*\z/', Rule::unique('listing_import_parsers')->ignore($parser)],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sample_text' => [Rule::requiredIf($request->filled('pattern') || $custom), 'nullable', 'string', 'max:12000'],
            'pattern' => [Rule::requiredIf($custom), 'nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($data['pattern'] ?? null) {
            $pattern = new PatternParser($data['pattern']);
            if (! $pattern->isValid()) {
                throw ValidationException::withMessages(['pattern' => 'الگو باید مدل و قیمت داشته باشد و هر جای‌نگهدار مجاز فقط یک‌بار استفاده شود.']);
            }
            if (! $pattern->matchesSample($data['sample_text'])) {
                throw ValidationException::withMessages(['sample_text' => 'حداقل یک ردیف از متن نمونه باید با الگو تطابق داشته باشد.']);
            }
        }

        return $data;
    }

    private function saved(ListingImportParser $parser): RedirectResponse
    {
        return redirect()->route('admin.listing-imports.index', ['parser' => $parser->key])
            ->with('status', 'قالب ایمپورت ذخیره شد.');
    }
}
