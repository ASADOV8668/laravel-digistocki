<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SavedListingSearch;
use App\Services\NaturalListingSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SavedListingSearchController extends Controller
{
    public function index(Request $request): View
    {
        $searches = SavedListingSearch::where('user_id', $request->user()->id)->latest()->paginate(20);

        return view('listings.saved-searches', compact('searches'));
    }

    public function store(Request $request, NaturalListingSearch $search): RedirectResponse
    {
        $query = $request->validate(['query' => ['required', 'string', 'max:160']])['query'];
        $criteria = $search->parse($query);
        if ($criteria === null) {
            throw ValidationException::withMessages(['query' => 'برای ذخیرهٔ جستجو، مدل گوشی را مشخص کنید.']);
        }

        SavedListingSearch::firstOrCreate([
            'user_id' => $request->user()->id, 'fingerprint' => hash('sha256', json_encode($criteria)),
        ], [
            'phone_model_id' => $criteria['phone_model_id'], 'query' => $query, 'criteria' => $criteria,
        ]);

        return redirect()->route('listings.searches.index')->with('status', 'جستجو ذخیره شد و اعلان‌های آن فعال شد.');
    }

    public function destroy(Request $request, int $search): RedirectResponse
    {
        SavedListingSearch::where('user_id', $request->user()->id)->findOrFail($search)->delete();

        return redirect()->route('listings.searches.index')->with('status', 'جستجو حذف شد.');
    }
}
