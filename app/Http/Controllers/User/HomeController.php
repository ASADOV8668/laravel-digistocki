<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Sadegh19b\LaravelIranCities\Models\Province;

class HomeController extends Controller
{
    public function index()
    {
        $latestListings = Listing::query()
            ->published()
            ->with(['brand', 'phoneModel', 'primaryImage', 'user.storefront'])
            ->latest('published_at')
            ->limit(8)
            ->get();

        return view('welcome', [
            'latestListings' => $latestListings,
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
