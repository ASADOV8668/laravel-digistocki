<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->input('status');
        $statuses = ['pending', 'approved', 'rejected', 'sold', 'expired'];

        $listings = Listing::query()
            ->where('user_id', $user->id)
            ->with(['brand', 'phoneModel', 'primaryImage'])
            ->when(in_array($status, $statuses, true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('dashboard', [
            'listings' => $listings,
            'status' => $status,
            'counts' => collect($statuses)->mapWithKeys(fn (string $item) => [$item => Listing::where('user_id', $user->id)->where('status', $item)->count()]),
        ]);
    }
}
