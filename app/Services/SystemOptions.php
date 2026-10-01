<?php

namespace App\Services;

use App\Models\SystemOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SystemOptions
{
    private const CACHE_KEY = 'system_options.values';

    public const DEFAULTS = [
        'site_title' => 'دیجی استوک',
        'page_title_prefix' => 'دیجی استوک',
        'title_separator' => '|',
        'max_image_upload_mb' => '5',
        'max_image_upload_count' => '5',
        'support_office_address' => '',
        'support_email' => '',
        'support_phone' => '',
        'listings_enabled' => '1',
        'allow_contact_price' => '1',
        'sms_mode' => 'test',
        'registration_mode' => 'mobile',
        'otp_expiry_minutes' => '5',
    ];

    private ?Collection $values = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();

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

    public function pageTitle(?string $page = null): string
    {
        $siteTitle = trim((string) $this->get('site_title')) ?: self::DEFAULTS['site_title'];
        $prefix = trim((string) $this->get('page_title_prefix'));
        $separator = trim((string) $this->get('title_separator')) ?: '|';

        if (! $page) {
            return $siteTitle;
        }

        return trim($prefix ?: $siteTitle).' '.$separator.' '.trim($page);
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
}
