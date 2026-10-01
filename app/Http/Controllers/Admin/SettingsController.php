<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemOptions;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit(SystemOptions $options)
    {
        return view('admin.settings.index', ['settings' => $options->all()]);
    }

    public function update(Request $request, SystemOptions $options)
    {
        $validated = $request->validate([
            'site_title' => ['required', 'string', 'max:100'],
            'page_title_prefix' => ['nullable', 'string', 'max:100'],
            'title_separator' => ['required', 'string', 'max:10'],
            'max_image_upload_mb' => ['required', 'integer', 'min:1', 'max:50'],
            'max_image_upload_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'support_office_address' => ['nullable', 'string', 'max:500'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'listings_enabled' => ['nullable', 'boolean'],
            'allow_contact_price' => ['nullable', 'boolean'],
            'sms_mode' => ['nullable', 'in:test,live'],
            'registration_mode' => ['nullable', 'in:mobile'],
            'otp_expiry_minutes' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $options->setMany([
            'site_title' => [$validated['site_title'], 'string'],
            'page_title_prefix' => [$validated['page_title_prefix'] ?? '', 'string'],
            'title_separator' => [$validated['title_separator'], 'string'],
            'max_image_upload_mb' => [(int) $validated['max_image_upload_mb'], 'integer'],
            'max_image_upload_count' => [(int) ($validated['max_image_upload_count'] ?? $options->maxImageUploadCount()), 'integer'],
            'support_office_address' => [$validated['support_office_address'] ?? '', 'string'],
            'support_email' => [$validated['support_email'] ?? '', 'string'],
            'support_phone' => [$validated['support_phone'] ?? '', 'string'],
            'listings_enabled' => [$request->boolean('listings_enabled'), 'boolean'],
            'allow_contact_price' => [$request->boolean('allow_contact_price'), 'boolean'],
            'sms_mode' => [$validated['sms_mode'] ?? $options->smsMode(), 'string'],
            'registration_mode' => [$validated['registration_mode'] ?? $options->registrationMode(), 'string'],
            'otp_expiry_minutes' => [(int) ($validated['otp_expiry_minutes'] ?? $options->otpExpiryMinutes()), 'integer'],
        ]);

        return back()->with('status', 'تنظیمات سیستم ذخیره شد.');
    }
}
