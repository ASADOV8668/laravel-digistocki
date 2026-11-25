<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Notifications\Notification;

class SavedListingSearchNotification extends Notification
{
    public function __construct(private readonly Listing $listing, private readonly bool $priceDrop) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->priceDrop ? 'کاهش قیمت در جستجوی ذخیره‌شده' : 'آگهی تازه در جستجوی ذخیره‌شده',
            'message' => $this->listing->title,
            'listing_id' => $this->listing->id,
            'url' => route('listings.show', $this->listing),
        ];
    }
}
