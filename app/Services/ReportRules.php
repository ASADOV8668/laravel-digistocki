<?php

namespace App\Services;

use App\Models\Report;

class ReportRules
{
    public const STATUSES = [
        'pending' => 'در انتظار',
        'reviewed' => 'بررسی‌شده',
        'resolved' => 'حل‌شده',
        'rejected' => 'ردشده',
    ];

    /**
     * Return the statuses that can be selected for a report in its current state.
     * Resolved and rejected reports are terminal to keep the moderation history reliable.
     *
     * @return array<string, string>
     */
    public function availableStatuses(Report $report): array
    {
        $allowed = match ($report->status) {
            'pending' => ['pending', 'reviewed', 'resolved', 'rejected'],
            'reviewed' => ['reviewed', 'resolved', 'rejected'],
            'resolved' => ['resolved'],
            'rejected' => ['rejected'],
            default => [$report->status],
        };

        return array_intersect_key(self::STATUSES, array_flip($allowed));
    }

    public function canTransition(Report $report, string $status): bool
    {
        return array_key_exists($status, $this->availableStatuses($report));
    }
}
