<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Listing;

class HomeController extends Controller
{
    public function index()
    {
        $latestListings = Listing::query()
            ->published()
            ->with(['brand', 'phoneModel', 'primaryImage'])
            ->latest('published_at')
            ->limit(8)
            ->get();

        return view('welcome', compact('latestListings'));
    }
}
