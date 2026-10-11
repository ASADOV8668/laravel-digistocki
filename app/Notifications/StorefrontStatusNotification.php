<?php

namespace App\Notifications;

use App\Models\SellerStore;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StorefrontStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly SellerStore $store,
        public readonly string $event,
        public readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $approved = $this->event === 'approved';

        return [
            'title' => $approved ? 'فروشگاه شما تأیید شد' : 'درخواست فروشگاه نیاز به اصلاح دارد',
            'message' => $approved
                ? 'فروشگاه شما توسط مدیریت تأیید شد و اکنون برای بازدیدکنندگان قابل نمایش است.'
                : 'درخواست فروشگاه شما رد شد. علت درج‌شده را بررسی و اطلاعات را اصلاح کنید.',
            'reason' => $this->reason,
            'status' => $this->event,
            'store_id' => $this->store->getKey(),
            'url' => route('storefront.edit'),
        ];
    }
}
