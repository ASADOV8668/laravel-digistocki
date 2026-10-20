<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit(SystemOptions $options)
    {
        return view('admin.settings.index', ['settings' => $options->all()]);
    }

    public function update(Request $request, SystemOptions $options): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'site_title' => ['required', 'string', 'max:100'],
            'page_title_suffix' => ['nullable', 'string', 'max:100'],
            // Accept the old field name for backward compatibility with older clients.
            'page_title_prefix' => ['nullable', 'string', 'max:100'],
            'title_separator' => ['required', 'string', 'max:10'],
            'max_image_upload_mb' => ['required', 'integer', 'min:1', 'max:50'],
            'max_image_upload_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'image_compression_percent' => ['nullable', 'integer', 'min:0', 'max:80'],
            'support_office_address' => ['nullable', 'string', 'max:500'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'listings_enabled' => ['nullable', 'boolean'],
            'allow_contact_price' => ['nullable', 'boolean'],
            'show_listing_views' => ['nullable', 'boolean'],
            'sms_mode' => ['nullable', 'in:test,live'],
            'registration_mode' => ['nullable', 'in:mobile'],
            'otp_expiry_minutes' => ['nullable', 'integer', 'min:1', 'max:30'],
            'listing_duplicate_cooldown_minutes' => ['nullable', 'integer', 'min:0', 'max:43200'],
            'session_lifetime_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'home_listings_per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
            'search_listings_per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
            'storefront_listings_per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
            'listings_pagination_type' => ['nullable', 'in:pagination,infinite'],
            'chat_archive_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $pageTitleSuffix = array_key_exists('page_title_suffix', $validated)
            ? ($validated['page_title_suffix'] ?? '')
            : ($validated['page_title_prefix'] ?? '');

        $options->setMany([
            'site_title' => [$validated['site_title'], 'string'],
            'page_title_suffix' => [$pageTitleSuffix, 'string'],
            // Keep the legacy key synchronized so existing integrations remain compatible.
            'page_title_prefix' => [$pageTitleSuffix, 'string'],
            'title_separator' => [$validated['title_separator'], 'string'],
            'max_image_upload_mb' => [(int) $validated['max_image_upload_mb'], 'integer'],
            'max_image_upload_count' => [(int) ($validated['max_image_upload_count'] ?? $options->maxImageUploadCount()), 'integer'],
            'image_compression_percent' => [(int) ($validated['image_compression_percent'] ?? $options->imageCompressionPercent()), 'integer'],
            'support_office_address' => [$validated['support_office_address'] ?? '', 'string'],
            'support_email' => [$validated['support_email'] ?? '', 'string'],
            'support_phone' => [$validated['support_phone'] ?? '', 'string'],
            'listings_enabled' => [$request->boolean('listings_enabled'), 'boolean'],
            'allow_contact_price' => [$request->boolean('allow_contact_price'), 'boolean'],
            'show_listing_views' => [$request->boolean('show_listing_views'), 'boolean'],
            'sms_mode' => [$validated['sms_mode'] ?? $options->smsMode(), 'string'],
            'registration_mode' => [$validated['registration_mode'] ?? $options->registrationMode(), 'string'],
            'otp_expiry_minutes' => [(int) ($validated['otp_expiry_minutes'] ?? $options->otpExpiryMinutes()), 'integer'],
            'listing_duplicate_cooldown_minutes' => [(int) ($validated['listing_duplicate_cooldown_minutes'] ?? $options->listingDuplicateCooldownMinutes()), 'integer'],
            'session_lifetime_days' => [(int) ($validated['session_lifetime_days'] ?? $options->sessionLifetimeDays()), 'integer'],
            'home_listings_per_page' => [(int) ($validated['home_listings_per_page'] ?? $options->homeListingsPerPage()), 'integer'],
            'search_listings_per_page' => [(int) ($validated['search_listings_per_page'] ?? $options->searchListingsPerPage()), 'integer'],
            'storefront_listings_per_page' => [(int) ($validated['storefront_listings_per_page'] ?? $options->storefrontListingsPerPage()), 'integer'],
            'listings_pagination_type' => [$validated['listings_pagination_type'] ?? $options->listingsPaginationType(), 'string'],
            'chat_archive_days' => [(int) ($validated['chat_archive_days'] ?? $options->chatArchiveDays()), 'integer'],
        ]);

        $message = 'تنظیمات سیستم ذخیره شد.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'settings' => $options->all(),
            ]);
        }

        return back()->with('status', $message);
    }
}
