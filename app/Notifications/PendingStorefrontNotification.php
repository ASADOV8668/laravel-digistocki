<?php

namespace App\Notifications;

use App\Models\SellerStore;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PendingStorefrontNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly SellerStore $store) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->store->loadMissing('user');

        return [
            'title' => 'فروشگاه منتظر تأیید است',
            'message' => 'فروشگاه «'.$this->store->name.'» متعلق به '.$this->store->user?->name.' برای بررسی مدیریت ثبت شده است.',
            'status' => 'pending',
            'store_id' => $this->store->getKey(),
            'user_id' => $this->store->user_id,
            'url' => route('admin.storefronts.show', $this->store),
        ];
    }
}
