<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Services\SystemOptions;

class StoreListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Listing::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'phone_model_id' => ['required', 'integer', 'exists:phone_models,id'],
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:0'],
            'is_negotiable' => ['nullable', 'boolean'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')->where(fn ($query) => $query->where('province_id', $this->input('province_id')))],
            'attributes' => ['nullable', 'array'],
            'attributes.*' => ['nullable'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:'.(app(SystemOptions::class)->maxImageUploadMb() * 1024)],
        ];
    }

    public function messages(): array
    {
        return [
            'brand_id.required' => 'انتخاب برند الزامی است.',
            'phone_model_id.required' => 'انتخاب مدل الزامی است.',
            'title.required' => 'عنوان آگهی را وارد کنید.',
            'price.required' => 'قیمت را وارد کنید.',
            'city_id.exists' => 'شهر انتخاب‌شده با استان انتخابی مطابقت ندارد.',
        ];
    }
}
