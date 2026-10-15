<?php

namespace App\Services;

use App\Models\User;

class NotificationReadService
{
    public function markTargetAsRead(User $user, string $dataKey, int|string $targetId): void
    {
        $targetId = (string) $targetId;

        $user->unreadNotifications()->get()->each(function ($notification) use ($dataKey, $targetId): void {
            if ((string) ($notification->data[$dataKey] ?? '') === $targetId) {
                $notification->markAsRead();
            }
        });
    }
}
