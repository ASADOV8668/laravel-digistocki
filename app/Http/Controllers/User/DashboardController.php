<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Brand;
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

        $listings = Listing::query()
            ->where('user_id', $user->id)
            ->with(['brand', 'phoneModel', 'primaryImage'])
            ->when(in_array($status, $statuses, true), fn ($query) => $query->where('status', $status))
            ->when($request->integer('brand_id'), fn (Builder $query) => $query->where('brand_id', $request->integer('brand_id')))
            ->when($request->integer('phone_model_id'), fn (Builder $query) => $query->where('phone_model_id', $request->integer('phone_model_id')))
            ->when($q !== '', function (Builder $query) use ($q): void {
                $term = '%'.$q.'%';
                $query->where(function (Builder $search) use ($term): void {
                    $search->where('title', 'like', $term)
                        ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', $term)->orWhere('name_en', 'like', $term))
                        ->orWhereHas('phoneModel', fn (Builder $model) => $model->where('name', 'like', $term)->orWhere('name_fa', 'like', $term)->orWhere('name_en', 'like', $term))
                        ->orWhereHas('attributeValues', fn (Builder $values) => $values->where('value_string', 'like', $term));
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

        return view('dashboard', [
            'listings' => $listings,
            'status' => $status,
            'counts' => collect($statuses)->mapWithKeys(fn (string $item) => [$item => (int) $countsByStatus->get($item, 0)]),
            'store' => $user->storefront,
            'brands' => Brand::query()->where('is_active', true)->with(['phoneModels' => fn ($query) => $query->where('is_active', true)->orderBy('name_fa')])->orderBy('name')->get(),
            'filters' => ['q' => $q, 'brand_id' => $request->input('brand_id'), 'phone_model_id' => $request->input('phone_model_id')],
        ]);
    }
}
