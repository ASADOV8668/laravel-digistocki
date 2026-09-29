<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = Report::query()
            ->where('user_id', $request->user()->id)
            ->with('listing')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('reports.index', compact('reports'));
    }

    public function store(Request $request, Listing $listing)
    {
        abort_unless($listing->status->value === 'approved' && ! $listing->isExpired(), 404);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $alreadyReported = Report::query()
            ->where('listing_id', $listing->id)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'reviewed'])
            ->exists();

        if ($alreadyReported) {
            return back()->withErrors(['report' => 'این آگهی را قبلاً گزارش کرده‌اید و در حال بررسی است.']);
        }

        Report::create([
            ...$validated,
            'listing_id' => $listing->id,
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return back()->with('status', 'گزارش شما ثبت شد و توسط تیم پشتیبانی بررسی می‌شود.');
    }
}
