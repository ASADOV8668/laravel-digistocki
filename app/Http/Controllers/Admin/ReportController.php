<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reportStats = Report::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $reports = Report::with(['listing', 'user'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.reports.index', compact('reports', 'reportStats'));
    }

    public function updateStatus(Request $request, Report $report)
    {
        $validated = $request->validate(['status' => ['required', Rule::in(['pending', 'reviewed', 'resolved', 'rejected'])]]);
        $report->update($validated);

        return back()->with('status', 'وضعیت گزارش به‌روزرسانی شد.');
    }
}
