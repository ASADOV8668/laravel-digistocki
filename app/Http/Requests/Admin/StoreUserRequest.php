<?php

namespace App\Http\Requests\Admin;

use App\Support\MobileNumber;
use App\Support\PersianDigits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mobile' => MobileNumber::normalize($this->input('mobile')),
            'national_id' => PersianDigits::toEnglish($this->input('national_id')),
            'is_active' => $this->boolean('is_active'),
            'can_post_listings' => $this->boolean('can_post_listings'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'mobile' => ['required', 'regex:/^09\d{9}$/', 'unique:users,mobile'],
            'national_id' => ['nullable', 'digits:10', 'unique:users,national_id'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['user', 'admin'])],
            'is_active' => ['boolean'],
            'can_post_listings' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام کاربر را وارد کنید.',
            'mobile.required' => 'شماره موبایل الزامی است.',
            'mobile.regex' => 'شماره موبایل باید با فرمت ۰۹xxxxxxxxx باشد.',
            'mobile.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'national_id.digits' => 'کد ملی باید ۱۰ رقم باشد.',
            'national_id.unique' => 'این کد ملی قبلاً ثبت شده است.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور صحیح نیست.',
        ];
    }
}
