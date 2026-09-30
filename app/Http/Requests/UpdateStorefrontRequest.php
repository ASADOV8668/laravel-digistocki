<?php

namespace App\Http\Requests;

use App\Services\SystemOptions;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStorefrontRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active ?? false;
    }

    public function rules(): array
    {
        $store = $this->user()?->storefront;
        $slugRules = ['nullable', 'string', 'min:3', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'];

        if ($store) {
            $slugRules[] = Rule::unique('seller_stores', 'slug')->ignore($store->id);
        } else {
            $slugRules[] = 'required_if:store_enabled,1';
            $slugRules[] = Rule::unique('seller_stores', 'slug');
        }

        return [
            'store_enabled' => ['nullable', 'boolean'],
            'name' => ['nullable', 'string', 'max:120', 'required_if:store_enabled,1'],
            'slug' => $slugRules,
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')->where(fn ($query) => $query->where('province_id', $this->input('province_id')))],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(app(SystemOptions::class)->maxImageUploadMb() * 1024)],
            'address' => ['nullable', 'string', 'max:1000'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $store = $this->user()?->storefront;
            if ($store && filled($this->input('slug')) && $this->input('slug') !== $store->slug) {
                $validator->errors()->add('slug', 'آدرس غرفه پس از ایجاد قابل تغییر نیست و فقط مدیریت می‌تواند آن را تغییر دهد.');
            }
        });
    }
}
