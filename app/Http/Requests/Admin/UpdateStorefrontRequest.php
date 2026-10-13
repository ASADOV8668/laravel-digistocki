<?php

namespace App\Http\Requests\Admin;

use App\Models\SellerStore;
use App\Services\SystemOptions;
use App\Support\PersianDate;
use App\Support\PersianDigits;
use App\Support\SocialHandle;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStorefrontRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $expiry = PersianDate::fromParts(
            $this->input('business_license_expires_at_year'),
            $this->input('business_license_expires_at_month'),
            $this->input('business_license_expires_at_day'),
        ) ?? $this->englishDigits($this->input('business_license_expires_at'));

        $this->merge([
            'slug' => $this->normalizeSlug($this->input('slug')),
            'store_enabled' => $this->boolean('store_enabled'),
            'admin_disabled' => $this->boolean('admin_disabled'),
            'national_id' => $this->englishDigits($this->input('national_id')),
            'contact_phone' => $this->englishDigits($this->input('contact_phone')),
            'business_license_expires_at' => $expiry,
            'approval_status' => $this->input('approval_status') ?: ($this->route('user')?->storefront?->approval_status ?? 'pending'),
            'listing_approval_mode' => $this->input('listing_approval_mode') ?: ($this->route('user')?->storefront?->listing_approval_mode ?? SellerStore::LISTING_APPROVAL_MANUAL),
            'instagram_url' => SocialHandle::normalize($this->input('instagram_url')),
            'telegram_url' => SocialHandle::normalize($this->input('telegram_url')),
            'whatsapp_url' => SocialHandle::normalize($this->input('whatsapp_url')),
            'bale_url' => SocialHandle::normalize($this->input('bale_url')),
            'rubika_url' => SocialHandle::normalize($this->input('rubika_url')),
            'remove_logo' => $this->boolean('remove_logo'),
            'remove_business_license' => $this->boolean('remove_business_license'),
        ]);
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $store = $user?->storefront;

        return [
            'store_enabled' => ['boolean'],
            'admin_disabled' => ['boolean'],
            'approval_status' => ['required', 'in:pending,approved,rejected'],
            'listing_approval_mode' => ['required', 'in:manual,free'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'regex:/^\d{10}$/'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('seller_stores', 'slug')->ignore($store?->id)],
            'business_license_number' => ['nullable', 'string', 'max:80'],
            'business_license_expires_at' => ['nullable', 'string', 'max:10'],
            'business_license_expires_at_year' => ['nullable', 'integer', 'between:1350,'.(int) PersianDate::format(now(), 'Y')],
            'business_license_expires_at_month' => ['nullable', 'integer', 'between:1,12'],
            'business_license_expires_at_day' => ['nullable', 'integer', 'between:1,31'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')->where(fn ($query) => $query->where('province_id', $this->input('province_id')))],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:'.(app(SystemOptions::class)->maxImageUploadMb() * 1024)],
            'business_license' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:'.(app(SystemOptions::class)->maxImageUploadMb() * 1024)],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_business_license' => ['nullable', 'boolean'],
            'address' => ['nullable', 'string', 'max:1000'],
            'contact_phone' => ['nullable', 'string', 'regex:/^09\d{9}$/'],
            'instagram_url' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._+\-]+$/'],
            'telegram_url' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._+\-]+$/'],
            'whatsapp_url' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._+\-]+$/'],
            'bale_url' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._+\-]+$/'],
            'rubika_url' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._+\-]+$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        if ($this->input('approval_status') === 'rejected' && ! filled($this->input('rejection_reason'))) {
            $validator->errors()->add('rejection_reason', 'برای وضعیت ردشده، علت رد را وارد کنید.');
        }

        $validator->after(function (Validator $validator): void {
            $nationalId = (string) $this->input('national_id');
            if (preg_match('/^\d{10}$/', $nationalId) === 1 && ! $this->isValidNationalId($nationalId)) {
                $validator->errors()->add('national_id', 'کد ملی واردشده معتبر نیست.');
            }

            $dateParts = array_filter([
                $this->input('business_license_expires_at_year'),
                $this->input('business_license_expires_at_month'),
                $this->input('business_license_expires_at_day'),
            ], static fn (mixed $part): bool => filled($part));

            if ($dateParts !== [] && count($dateParts) !== 3) {
                $validator->errors()->add('business_license_expires_at', 'روز، ماه و سال تاریخ اعتبار جواز کسب را کامل انتخاب کنید.');
            } elseif (filled($this->input('business_license_expires_at')) && ! PersianDate::parseDate($this->input('business_license_expires_at'))) {
                $validator->errors()->add('business_license_expires_at', 'تاریخ اعتبار جواز کسب معتبر نیست.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'شناسه اینترنتی فروشگاه فقط می‌تواند شامل حروف انگلیسی کوچک، عدد و خط تیره باشد؛ آدرس دقیق فروشگاه می‌تواند فارسی باشد.',
            'contact_phone.regex' => 'شماره تماس باید دقیقاً ۱۱ رقم و با ۰۹ شروع شود.',
            'national_id.regex' => 'کد ملی باید دقیقاً ۱۰ رقم باشد.',
            'instagram_url.regex' => 'نام کاربری اینستاگرام باید فقط شامل حروف انگلیسی، عدد، نقطه، زیرخط یا خط تیره باشد.',
            'telegram_url.regex' => 'نام کاربری تلگرام باید فقط شامل حروف انگلیسی، عدد، نقطه، زیرخط یا خط تیره باشد.',
            'whatsapp_url.regex' => 'شناسه واتساپ باید فقط شامل حروف انگلیسی، عدد، نقطه، زیرخط یا خط تیره باشد.',
            'bale_url.regex' => 'نام کاربری بله باید فقط شامل حروف انگلیسی، عدد، نقطه، زیرخط یا خط تیره باشد.',
            'rubika_url.regex' => 'نام کاربری روبیکا باید فقط شامل حروف انگلیسی، عدد، نقطه، زیرخط یا خط تیره باشد.',
        ];
    }

    private function isValidNationalId(string $value): bool
    {
        if (count(array_unique(str_split($value))) === 1) {
            return false;
        }
        $sum = 0;
        for ($index = 0; $index < 9; $index++) {
            $sum += (int) $value[$index] * (10 - $index);
        }
        $remainder = $sum % 11;
        $control = (int) $value[9];

        return $remainder < 2 ? $control === $remainder : $control === 11 - $remainder;
    }

    private function englishDigits(mixed $value): ?string
    {
        return $value === null ? null : PersianDigits::toEnglish((string) $value);
    }

    private function normalizeSlug(mixed $value): ?string
    {
        return $value === null ? null : strtolower(trim((string) $value));
    }
}
