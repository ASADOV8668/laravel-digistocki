<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Services\SystemOptions;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Sadegh19b\LaravelIranCities\Models\Province;

class HomeController extends Controller
{
    public function index(Request $request, SystemOptions $options): View|JsonResponse
    {
        $latestListings = Listing::query()
            ->published()
            ->with(['brand', 'phoneModel', 'province', 'city', 'primaryImage', 'attributeValues.attribute', 'user.storefront'])
            ->withViewerFavorite(auth()->id())
            ->latest('published_at')
            ->paginate($options->homeListingsPerPage())
            ->withQueryString();

        $paginationMode = $options->listingsPaginationType();

        if ($request->expectsJson()) {
            $view = $request->boolean('infinite')
                ? 'home.partials.latest-cards'
                : 'home.partials.latest-listings';

            return response()->json([
                'html' => view($view, compact('latestListings', 'paginationMode'))->render(),
                'next_page_url' => $latestListings->nextPageUrl(),
                'has_more' => $latestListings->hasMorePages(),
            ]);
        }

        return view('welcome', [
            'latestListings' => $latestListings,
            'paginationMode' => $paginationMode,
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
