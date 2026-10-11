<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Enums\AttributeType;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\PhoneModel;
use App\Services\SystemOptions;
use App\Support\PersianDate;
use App\Support\PersianSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $user->load('storefront');
        $status = $request->input('status');
        $statuses = ['pending', 'approved', 'rejected', 'sold', 'expired'];
        $q = trim((string) $request->input('q'));
        $searchLikes = PersianSearch::likes($q);
        $fromDateValue = PersianDate::fromParts($request->input('from_date_year'), $request->input('from_date_month'), $request->input('from_date_day')) ?: $request->input('from_date');
        $toDateValue = PersianDate::fromParts($request->input('to_date_year'), $request->input('to_date_month'), $request->input('to_date_day')) ?: $request->input('to_date');
        $fromDate = PersianDate::parseDate($fromDateValue);
        $toDate = PersianDate::parseDate($toDateValue);
        $dateRangeInvalid = $fromDate && $toDate && $fromDate->gt($toDate);
        $selectedBrandId = $request->integer('brand_id');
        $selectedModelId = $request->integer('phone_model_id');
        $selectedModel = $selectedModelId
            ? PhoneModel::query()
                ->where('is_active', true)
                ->when($selectedBrandId, fn (Builder $query) => $query->where('brand_id', $selectedBrandId))
                ->with(['attributes' => fn ($query) => $query->where('attributes.is_active', true)->where('attributes.is_filterable', true)->orderBy('attributes.sort_order')])
                ->find($selectedModelId)
            : null;
        $filterAttributes = $selectedModel?->attributes ?? collect();
        $filterAttributesById = $filterAttributes->keyBy('id');

        $listingQuery = Listing::query()
            ->where('user_id', $user->id)
            ->with(['brand', 'phoneModel', 'primaryImage'])
            ->when(in_array($status, $statuses, true), fn ($query) => $query->where('status', $status))
            ->when($selectedBrandId, fn (Builder $query) => $query->where('brand_id', $selectedBrandId))
            ->when($selectedModelId, fn (Builder $query) => $query->where('phone_model_id', $selectedModelId)->where('brand_id', $selectedModel?->brand_id ?? $selectedBrandId))
            ->when($fromDate && ! $dateRangeInvalid, fn (Builder $query) => $query->where('created_at', '>=', $fromDate->copy()->startOfDay()))
            ->when($toDate && ! $dateRangeInvalid, fn (Builder $query) => $query->where('created_at', '<=', $toDate->copy()->endOfDay()))
            ->when($searchLikes !== [], function (Builder $query) use ($searchLikes): void {
                $query->where(function (Builder $search) use ($searchLikes): void {
                    foreach ($searchLikes as $like) {
                        $search->orWhere('title', 'like', $like)
                            ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', $like)->orWhere('name_en', 'like', $like))
                            ->orWhereHas('phoneModel', fn (Builder $model) => $model->where('name', 'like', $like)->orWhere('name_fa', 'like', $like)->orWhere('name_en', 'like', $like))
                            ->orWhereHas('attributeValues', fn (Builder $values) => $values->where(function (Builder $value) use ($like): void {
                                $value->where('value_string', 'like', $like)
                                    ->orWhere('value_integer', 'like', $like)
                                    ->orWhere('value_decimal', 'like', $like);
                            }));
                    }
                });
            })
            ->when($filterAttributesById->isNotEmpty(), function (Builder $query) use ($request, $filterAttributesById): void {
                foreach ((array) $request->input('filters', []) as $attributeId => $value) {
                    $attribute = $filterAttributesById->get((int) $attributeId);
                    $values = array_values(array_filter((array) $value, fn ($item) => $item !== null && $item !== ''));
                    if (! $attribute || $values === []) {
                        continue;
                    }

                    $query->whereHas('attributeValues', function (Builder $attributeQuery) use ($attribute, $values): void {
                        $attributeQuery->where('attribute_id', $attribute->id);
                        if ($attribute->type === AttributeType::MultiSelect) {
                            foreach ($values as $filterValue) {
                                if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                                    $attributeQuery->whereRaw("JSON_SEARCH(value_json, 'one', ?) IS NOT NULL", [$filterValue]);
                                } else {
                                    $attributeQuery->whereJsonContains('value_json', $filterValue);
                                }
                            }

                            return;
                        }

                        $filterValue = $values[0];
                        match ($attribute->type) {
                            AttributeType::Integer => $attributeQuery->where('value_integer', (int) $filterValue),
                            AttributeType::Decimal => $attributeQuery->where('value_decimal', (float) $filterValue),
                            AttributeType::Boolean => $attributeQuery->where('value_boolean', filter_var($filterValue, FILTER_VALIDATE_BOOLEAN)),
                            default => $attributeQuery->where('value_string', $filterValue),
                        };
                    });
                }
            });

        $matchingListingIds = (clone $listingQuery)
            ->reorder()
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->values()
            ->all();

        $listings = $listingQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $countsByStatus = Listing::query()
            ->where('user_id', $user->id)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $brands = Brand::query()->where('is_active', true)->with(['phoneModels' => fn ($query) => $query->where('is_active', true)->orderBy('name_fa')])->orderBy('name')->get();
        $modelBrands = $request->integer('brand_id')
            ? $brands->where('id', $request->integer('brand_id'))
            : $brands;

        return view('dashboard', [
            'listings' => $listings,
            'status' => $status,
            'counts' => collect($statuses)->mapWithKeys(fn (string $item) => [$item => (int) $countsByStatus->get($item, 0)]),
            'store' => $user->storefront,
            'brands' => $brands,
            'modelBrands' => $modelBrands,
            'selectedModel' => $selectedModel,
            'filterAttributes' => $filterAttributes,
            'allowContactPrice' => app(SystemOptions::class)->allowContactPrice(),
            'visibleListingIds' => $listings->getCollection()->pluck('id')->map(fn (int $id): string => (string) $id)->values()->all(),
            'matchingListingIds' => $matchingListingIds,
            'dateRangeInvalid' => $dateRangeInvalid,
            'filters' => [
                'q' => $q,
                'brand_id' => $request->input('brand_id'),
                'phone_model_id' => $request->input('phone_model_id'),
                'from_date' => $fromDateValue,
                'to_date' => $toDateValue,
            ],
        ]);
    }
}
