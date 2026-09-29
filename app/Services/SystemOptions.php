<?php

namespace App\Services;

use App\Models\SystemOption;
use Illuminate\Support\Collection;

class SystemOptions
{
    public const DEFAULTS = [
        'site_title' => 'دیجی استوک',
        'page_title_prefix' => 'دیجی استوک',
        'title_separator' => '|',
        'max_image_upload_mb' => '5',
        'support_office_address' => '',
        'support_email' => '',
        'support_phone' => '',
        'listings_enabled' => '1',
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
        $this->values = null;
    }

    public function setMany(array $values): void
    {
        foreach ($values as $key => [$value, $type]) {
            $this->set($key, $value, $type);
        }
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

    public function listingsEnabled(): bool
    {
        return $this->bool('listings_enabled');
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

        $this->values = SystemOption::query()->pluck('value', 'key');
    }

    private function stringify(mixed $value): string
    {
        return is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    }
}
