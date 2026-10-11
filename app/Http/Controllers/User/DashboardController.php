<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Listing;
use App\Services\SystemOptions;
use App\Support\PersianDate;
use App\Support\PersianSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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
        $fromDate = PersianDate::parseDate($request->input('from_date'));
        $toDate = PersianDate::parseDate($request->input('to_date'));

        $listings = Listing::query()
            ->where('user_id', $user->id)
            ->with(['brand', 'phoneModel', 'primaryImage'])
            ->when(in_array($status, $statuses, true), fn ($query) => $query->where('status', $status))
            ->when($request->integer('brand_id'), fn (Builder $query) => $query->where('brand_id', $request->integer('brand_id')))
            ->when($request->integer('phone_model_id'), fn (Builder $query) => $query->where('phone_model_id', $request->integer('phone_model_id')))
            ->when($fromDate, fn (Builder $query) => $query->where('created_at', '>=', $fromDate->copy()->startOfDay()))
            ->when($toDate, fn (Builder $query) => $query->where('created_at', '<=', $toDate->copy()->endOfDay()))
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
            'allowContactPrice' => app(SystemOptions::class)->allowContactPrice(),
            'visibleListingIds' => $listings->getCollection()->pluck('id')->map(fn (int $id): string => (string) $id)->values()->all(),
            'filters' => [
                'q' => $q,
                'brand_id' => $request->input('brand_id'),
                'phone_model_id' => $request->input('phone_model_id'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
            ],
        ]);
    }
}
