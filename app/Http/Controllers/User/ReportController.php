<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $created = DB::transaction(function () use ($listing, $request, $validated): bool {
            $lockedListing = Listing::query()->whereKey($listing->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedListing->status->value === 'approved' && ! $lockedListing->isExpired(), 404);
            abort_if($lockedListing->isOwnedBy($request->user()), 422, 'نمی‌توانید آگهی خودتان را گزارش کنید.');

            $alreadyReported = Report::query()
                ->where('listing_id', $lockedListing->id)
                ->where('user_id', $request->user()->id)
                ->whereIn('status', ['pending', 'reviewed'])
                ->exists();

            if ($alreadyReported) {
                return false;
            }

            Report::create([
                ...$validated,
                'listing_id' => $lockedListing->id,
                'user_id' => $request->user()->id,
                'status' => 'pending',
            ]);

            return true;
        });

        if (! $created) {
            return back()->withErrors(['report' => 'این آگهی را قبلاً گزارش کرده‌اید و در حال بررسی است.']);
        }

        return back()->with('status', 'گزارش شما ثبت شد و توسط تیم پشتیبانی بررسی می‌شود.');
    }
}
