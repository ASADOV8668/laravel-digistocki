<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ReportRules;
use App\Support\PersianDate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request, ReportRules $reportRules)
    {
        $status = $request->input('status');
        $status = is_string($status) && array_key_exists($status, ReportRules::STATUSES) ? $status : null;
        $dateFrom = PersianDate::parseDate($request->input('date_from'));
        $dateTo = PersianDate::parseDate($request->input('date_to'));

        $reportStats = Report::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $reports = Report::with(['listing', 'user'])
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($dateFrom, fn ($query) => $query->where('created_at', '>=', $dateFrom->startOfDay()))
            ->when($dateTo, fn ($query) => $query->where('created_at', '<=', $dateTo->endOfDay()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $reportStatusOptions = $reports->getCollection()
            ->mapWithKeys(fn (Report $report) => [$report->id => $reportRules->availableStatuses($report)])
            ->all();

        return view('admin.reports.index', compact('reports', 'reportStats', 'reportStatusOptions'));
    }

    public function updateStatus(Request $request, Report $report, ReportRules $reportRules)
    {
        $validated = $request->validate(['status' => ['required', Rule::in(['pending', 'reviewed', 'resolved', 'rejected'])]]);

        abort_unless($reportRules->canTransition($report, $validated['status']), 422, 'این گزارش دیگر قابل تغییر نیست.');

        $report->update($validated);

        return back()->with('status', 'وضعیت گزارش به‌روزرسانی شد.');
    }
}
