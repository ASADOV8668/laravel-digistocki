<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\Report;
use App\Models\SellerStore;
use App\Support\PersianDate;
use App\Support\PersianNumber;

class BaleAdminMenu
{
    public const REJECTION_REASONS = [
        'photo' => 'عکس آگهی نامناسب است.',
        'details' => 'اطلاعات آگهی ناقص است.',
        'duplicate' => 'آگهی تکراری است.',
    ];

    public function home(): array
    {
        $pending = Listing::where('status', ListingStatus::Pending)->count();
        $stores = SellerStore::where('approval_status', 'pending')->count();
        $reports = Report::where('status', 'pending')->count();

        return ["مدیریت دیجی استوکی\nیکی از بخش‌ها را انتخاب کنید.", [
            [['text' => 'آگهی‌های منتظر ('.PersianNumber::digits($pending).')', 'callback_data' => 'queue']],
            [
                ['text' => 'فروشگاه‌ها ('.PersianNumber::digits($stores).')', 'url' => route('admin.storefronts.index', ['approval_status' => 'pending'])],
                ['text' => 'گزارش‌ها ('.PersianNumber::digits($reports).')', 'url' => route('admin.reports.index', ['status' => 'pending'])],
            ],
            [
                ['text' => 'خلاصه امروز', 'callback_data' => 'report'],
                ['text' => 'پنل مدیریت', 'url' => route('admin.dashboard')],
            ],
        ]];
    }

    public function queue(?string $after = null): array
    {
        $listing = Listing::query()->where('status', ListingStatus::Pending)
            ->when($after !== null, fn ($query) => $query->where('id', '>', $after))
            ->oldest('id')->first();

        return $listing
            ? $this->card($listing)
            : [$after === null ? 'آگهی در انتظار بررسی وجود ندارد.' : 'به پایان صف رسیدید.', $this->reportButtons()];
    }

    public function card(Listing $listing): array
    {
        if ($listing->status !== ListingStatus::Pending) {
            return [$this->heading($listing)."\nوضعیت فعلی: ".$listing->status->label(), $this->reportButtons()];
        }

        $store = $listing->user?->storefront?->name;
        $price = $listing->price_on_request || $listing->price === null
            ? 'توافقی'
            : PersianNumber::format($listing->price).' تومان';
        $text = $this->heading($listing)."\nقیمت: ".$price."\nزمان ثبت: ".PersianDate::human($listing->created_at);
        if (filled($store)) {
            $text .= "\nفروشگاه: ".mb_substr($store, 0, 100);
        }

        return [$text, [
            [
                ['text' => 'تأیید', 'callback_data' => "approve:{$listing->id}", 'style' => 'success'],
                ['text' => 'رد با دلیل', 'callback_data' => "reject:{$listing->id}", 'style' => 'danger'],
            ],
            [
                ['text' => 'جزئیات و عکس‌ها در پنل', 'url' => route('admin.listings.edit', $listing)],
                ['text' => 'آگهی بعدی', 'callback_data' => "queue:{$listing->id}"],
            ],
            [['text' => 'منوی اصلی', 'callback_data' => 'menu']],
        ]];
    }

    public function reasons(Listing $listing): array
    {
        $buttons = [];
        foreach (self::REJECTION_REASONS as $key => $reason) {
            $buttons[] = [['text' => rtrim($reason, '.'), 'callback_data' => "reason:{$listing->id}:{$key}"]];
        }
        $buttons[] = [
            ['text' => 'دلیل دیگر', 'callback_data' => "custom:{$listing->id}"],
            ['text' => 'بازگشت', 'callback_data' => "view:{$listing->id}"],
        ];

        return [$this->heading($listing)."\nدلیل رد را انتخاب کنید.", $buttons];
    }

    public function confirmation(Listing $listing, ?string $reason, string $callback): array
    {
        $action = $reason === null ? 'تأیید' : 'رد';
        $text = $this->heading($listing)."\nاین آگهی {$action} شود؟";
        if ($reason !== null) {
            $text .= "\nدلیل: ".$reason;
        }

        return [$text, [[
            ['text' => 'ثبت '.$action, 'callback_data' => $callback, 'style' => $reason === null ? 'success' : 'danger'],
            ['text' => 'انصراف', 'callback_data' => "view:{$listing->id}"],
        ]]];
    }

    public function reportButtons(): array
    {
        return [[
            ['text' => 'شروع بررسی', 'callback_data' => 'queue'],
            ['text' => 'منوی اصلی', 'callback_data' => 'menu'],
        ]];
    }

    private function heading(Listing $listing): string
    {
        return mb_substr($listing->title, 0, 160)."\nآگهی #".PersianNumber::digits($listing->id);
    }
}
