<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PendingListingNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Listing $listing) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->listing->loadMissing(['user', 'brand', 'phoneModel']);

        return [
            'title' => 'آگهی منتظر تأیید است',
            'message' => 'آگهی «'.$this->listing->title.'» متعلق به '.$this->listing->user?->name.' برای بررسی مدیریت ثبت شده است.',
            'status' => 'pending',
            'listing_id' => $this->listing->getKey(),
            'user_id' => $this->listing->user_id,
            'url' => route('admin.listings.edit', $this->listing),
        ];
    }
}
