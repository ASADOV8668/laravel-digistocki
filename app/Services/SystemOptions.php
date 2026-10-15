<?php

namespace App\Services;

use App\Models\SystemOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SystemOptions
{
    private const CACHE_KEY = 'system_options.values';

    public const DEFAULTS = [
        'site_title' => 'دیجی استوکی',
        'page_title_suffix' => 'دیجی استوکی',
        // Kept as a compatibility alias for older integrations and stored rows.
        'page_title_prefix' => 'دیجی استوکی',
        'title_separator' => '|',
        'max_image_upload_mb' => '5',
        'max_image_upload_count' => '5',
        'image_compression_percent' => '20',
        'support_office_address' => '',
        'support_email' => '',
        'support_phone' => '',
        'listings_enabled' => '1',
        'allow_contact_price' => '1',
        'sms_mode' => 'test',
        'registration_mode' => 'mobile',
        'otp_expiry_minutes' => '5',
        // 24 hours expressed in minutes. Zero means no duplicate-listing limit.
        'listing_duplicate_cooldown_minutes' => '1440',
        'session_lifetime_days' => '365',
        'home_listings_per_page' => '100',
        'search_listings_per_page' => '100',
        'storefront_listings_per_page' => '100',
        'listings_pagination_type' => 'pagination',
    ];

    private ?Collection $values = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();

        // Older installations stored this setting as page_title_prefix. Read it
        // transparently until the administrator saves the renamed setting.
        if ($key === 'page_title_suffix' && ! $this->values->has('page_title_suffix')) {
            return $this->values->get('page_title_prefix', $default ?? self::DEFAULTS[$key] ?? null);
        }

        return $this->values->get($key, $default ?? (self::DEFAULTS[$key] ?? null));
    }

    public function set(string $key, mixed $value, string $type = 'string'): void
    {
        SystemOption::updateOrCreate(['key' => $key], ['value' => $this->stringify($value), 'type' => $type]);
        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }

    public function setMany(array $values): void
    {
        if ($values === []) {
            $this->values = null;

            return;
        }

        $timestamp = now();
        $rows = [];

        foreach ($values as $key => [$value, $type]) {
            $rows[] = [
                'key' => $key,
                'value' => $this->stringify($value),
                'type' => $type,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        SystemOption::query()->upsert(
            $rows,
            ['key'],
            ['value', 'type', 'updated_at'],
        );

        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }

    public function all(): array
    {
        return collect(self::DEFAULTS)->mapWithKeys(fn ($default, $key) => [$key => $this->get($key, $default)])->all();
    }

    public function bool(string $key): bool
    {
        return filter_var($this->get($key), FILTER_VALIDATE_BOOLEAN);
    }

    public function int(string $key): int
    {
        return max(1, (int) $this->get($key));
    }

    public function maxImageUploadMb(): int
    {
        return min(50, $this->int('max_image_upload_mb'));
    }

    public function maxImageUploadCount(): int
    {
        return min(20, $this->int('max_image_upload_count'));
    }

    public function imageCompressionPercent(): int
    {
        return min(80, max(0, (int) $this->get('image_compression_percent')));
    }

    public function listingsEnabled(): bool
    {
        return $this->bool('listings_enabled');
    }

    public function allowContactPrice(): bool
    {
        return $this->bool('allow_contact_price');
    }

    public function smsMode(): string
    {
        return in_array($mode = (string) $this->get('sms_mode'), ['test', 'live'], true) ? $mode : 'test';
    }

    public function registrationMode(): string
    {
        $mode = (string) $this->get('registration_mode');

        return in_array($mode, ['mobile'], true) ? $mode : 'mobile';
    }

    public function otpExpiryMinutes(): int
    {
        return min(30, max(1, (int) $this->get('otp_expiry_minutes')));
    }

    public function listingDuplicateCooldownMinutes(): int
    {
        return min(43200, max(0, (int) $this->get('listing_duplicate_cooldown_minutes')));
    }

    public function sessionLifetimeDays(): int
    {
        return min(3650, max(1, (int) $this->get('session_lifetime_days')));
    }

    public function sessionLifetimeMinutes(): int
    {
        return $this->sessionLifetimeDays() * 1440;
    }

    public function homeListingsPerPage(): int
    {
        return $this->listingsPerPage('home_listings_per_page');
    }

    public function searchListingsPerPage(): int
    {
        return $this->listingsPerPage('search_listings_per_page');
    }

    public function storefrontListingsPerPage(): int
    {
        return $this->listingsPerPage('storefront_listings_per_page');
    }

    public function listingsPaginationType(): string
    {
        $type = (string) $this->get('listings_pagination_type', 'pagination');

        return in_array($type, ['pagination', 'infinite'], true) ? $type : 'pagination';
    }

    public function pageTitle(?string $page = null): string
    {
        $siteTitle = trim((string) $this->get('site_title')) ?: self::DEFAULTS['site_title'];
        $suffix = trim((string) $this->get('page_title_suffix'));
        $separator = trim((string) $this->get('title_separator')) ?: '|';

        if (! $page) {
            return $siteTitle;
        }

        if (! $this->values->has('page_title_suffix') && ! $this->values->has('page_title_prefix')) {
            $suffix = $siteTitle;
        }

        return trim($page).' '.$separator.' '.($suffix ?: $siteTitle);
    }

    public function siteDescription(): string
    {
        $siteTitle = trim((string) $this->get('site_title')) ?: self::DEFAULTS['site_title'];

        return 'خرید و فروش مطمئن موبایل با آگهی‌های بررسی‌شده و جستجوی هوشمند در '.$siteTitle.'.';
    }

    private function load(): void
    {
        if ($this->values !== null) {
            return;
        }

        $this->values = collect(Cache::rememberForever(
            self::CACHE_KEY,
            fn () => SystemOption::query()->pluck('value', 'key')->all(),
        ));
    }

    private function stringify(mixed $value): string
    {
        return is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    }

    private function listingsPerPage(string $key): int
    {
        return min(500, max(1, (int) $this->get($key, 100)));
    }
}
