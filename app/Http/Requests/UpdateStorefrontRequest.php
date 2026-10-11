<?php

namespace App\Http\Requests;

use App\Services\SystemOptions;
use App\Support\PersianDate;
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
        $this->merge([
            'national_id' => $this->englishDigits($this->input('national_id')),
            'contact_phone' => $this->englishDigits($this->input('contact_phone')),
            'business_license_expires_at' => $this->englishDigits($this->input('business_license_expires_at')),
            'store_enabled' => $this->boolean('store_enabled'),
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
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where(fn ($query) => $query->where('province_id', $this->input('province_id')))],
            'address' => ['required', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(app(SystemOptions::class)->maxImageUploadMb() * 1024)],
            'business_license' => [Rule::requiredIf(! $store?->business_license_path), 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(app(SystemOptions::class)->maxImageUploadMb() * 1024)],
            'contact_phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'telegram_url' => ['nullable', 'url', 'max:255'],
            'whatsapp_url' => ['nullable', 'url', 'max:255'],
            'bale_url' => ['nullable', 'url', 'max:255'],
            'rubika_url' => ['nullable', 'url', 'max:255'],
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

            if (filled($this->input('business_license_expires_at')) && ! PersianDate::parseDate($this->input('business_license_expires_at'))) {
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
