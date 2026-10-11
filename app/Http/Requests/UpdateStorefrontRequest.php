<?php

namespace App\Http\Requests;

use App\Services\SystemOptions;
use App\Support\PersianDate;
use App\Support\SocialHandle;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStorefrontRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active ?? false;
    }

    protected function prepareForValidation(): void
    {
        $expiry = PersianDate::fromParts(
            $this->input('business_license_expires_at_year'),
            $this->input('business_license_expires_at_month'),
            $this->input('business_license_expires_at_day'),
        ) ?? $this->englishDigits($this->input('business_license_expires_at'));

        $this->merge([
            'national_id' => $this->englishDigits($this->input('national_id')),
            'contact_phone' => $this->englishDigits($this->input('contact_phone')),
            'business_license_expires_at' => $expiry,
            'store_enabled' => $this->boolean('store_enabled'),
            'instagram_url' => SocialHandle::normalize($this->input('instagram_url')),
            'telegram_url' => SocialHandle::normalize($this->input('telegram_url')),
            'whatsapp_url' => SocialHandle::normalize($this->input('whatsapp_url')),
            'bale_url' => SocialHandle::normalize($this->input('bale_url')),
            'rubika_url' => SocialHandle::normalize($this->input('rubika_url')),
        ]);
    }

    public function rules(): array
    {
        $store = $this->user()?->storefront;
        $slugRules = ['string', 'min:3', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'];

        if ($store) {
            $slugRules[] = 'nullable';
            $slugRules[] = Rule::unique('seller_stores', 'slug')->ignore($store->id);
        } else {
            $slugRules[] = 'required';
            $slugRules[] = Rule::unique('seller_stores', 'slug');
        }

        return [
            'store_enabled' => ['nullable', 'boolean'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:100'],
            'national_id' => ['required', 'string', 'regex:/^\d{10}$/'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => $slugRules,
            'business_license_number' => ['required', 'string', 'max:80'],
            'business_license_expires_at' => ['required', 'string', 'max:10'],
            'business_license_expires_at_year' => ['nullable', 'integer', 'between:1350,'.(int) PersianDate::format(now(), 'Y')],
            'business_license_expires_at_month' => ['nullable', 'integer', 'between:1,12'],
            'business_license_expires_at_day' => ['nullable', 'integer', 'between:1,31'],
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where(fn ($query) => $query->where('province_id', $this->input('province_id')))],
            'address' => ['required', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(app(SystemOptions::class)->maxImageUploadMb() * 1024)],
            'business_license' => [Rule::requiredIf(! $store?->business_license_path), 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(app(SystemOptions::class)->maxImageUploadMb() * 1024)],
            'contact_phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'instagram_url' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._+\-]+$/'],
            'telegram_url' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._+\-]+$/'],
            'whatsapp_url' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._+\-]+$/'],
            'bale_url' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._+\-]+$/'],
            'rubika_url' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._+\-]+$/'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $store = $this->user()?->storefront;
            if ($store && filled($this->input('slug')) && $this->input('slug') !== $store->slug) {
                $validator->errors()->add('slug', 'آدرس فروشگاه پس از ایجاد قابل تغییر نیست و فقط مدیریت می‌تواند آن را تغییر دهد.');
            }

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
            'slug.regex' => 'آدرس فروشگاه فقط می‌تواند شامل حروف انگلیسی کوچک، عدد و خط تیره باشد.',
            'contact_phone.regex' => 'شماره تماس باید دقیقاً ۱۱ رقم و با ۰۹ شروع شود.',
            'national_id.regex' => 'کد ملی باید دقیقاً ۱۰ رقم باشد.',
            'business_license.image' => 'تصویر جواز کسب باید یک فایل تصویری معتبر باشد.',
            'business_license.max' => 'حجم تصویر جواز کسب بیشتر از حد مجاز تنظیمات است.',
        ];
    }

    private function englishDigits(mixed $value): ?string
    {
        return $value === null ? null : strtr((string) $value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
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
}
