<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ListingStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Listing $listing,
        public readonly string $event,
        public readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        [$title, $message] = match ($this->event) {
            'approved' => ['آگهی تأیید شد', 'آگهی شما تأیید و در سایت منتشر شد.'],
            'rejected' => ['آگهی نیاز به اصلاح دارد', 'آگهی شما رد شد و نیاز به بررسی مجدد دارد.'],
            'expired' => ['آگهی منقضی شد', 'مهلت نمایش آگهی شما به پایان رسیده است.'],
            default => ['به‌روزرسانی آگهی', 'وضعیت آگهی شما تغییر کرد.'],
        };

        return [
            'title' => $title,
            'message' => $message,
            'reason' => $this->reason,
            'status' => $this->event,
            'listing_id' => $this->listing->getKey(),
            'url' => route('listings.show', ['listing' => $this->listing->id]),
        ];
    }
}
