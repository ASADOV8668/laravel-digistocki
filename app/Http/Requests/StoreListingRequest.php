<?php

namespace App\Http\Requests;

use App\Models\Listing;
use App\Models\PhoneModel;
use App\Services\ListingAttributeValidator;
use App\Services\SystemOptions;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Listing::class) ?? false;
    }

    public function rules(): array
    {
        $priceRules = ['nullable', 'integer', 'min:0'];
        if (app(SystemOptions::class)->allowContactPrice()) {
            $priceRules[] = 'required_unless:price_on_request,1';
        } else {
            $priceRules[] = 'required';
        }

        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'phone_model_id' => ['required', 'integer', 'exists:phone_models,id'],
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => $priceRules,
            'price_on_request' => ['nullable', 'boolean'],
            'is_negotiable' => ['nullable', 'boolean'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')->where(fn ($query) => $query->where('province_id', $this->input('province_id')))],
            'attributes' => ['nullable', 'array'],
            'attributes.*' => ['nullable'],
            'images' => ['nullable', 'array', 'max:'.app(SystemOptions::class)->maxImageUploadCount()],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:'.(app(SystemOptions::class)->maxImageUploadMb() * 1024)],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('price_on_request') && ! app(SystemOptions::class)->allowContactPrice()) {
                $validator->errors()->add('price_on_request', 'امکان انتخاب «تماس بگیرید» در تنظیمات سیستم فعال نیست.');
            }

            $model = PhoneModel::query()->find($this->integer('phone_model_id'));
            if (! $model || $model->brand_id !== $this->integer('brand_id')) {
                return;
            }

            $errors = app(ListingAttributeValidator::class)->errors($model, (array) $this->input('attributes', []));

            foreach ($errors as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        });
    }

    public function messages(): array
    {
        return [
            'brand_id.required' => 'انتخاب برند الزامی است.',
            'phone_model_id.required' => 'انتخاب مدل الزامی است.',
            'title.required' => 'عنوان آگهی را وارد کنید.',
            'price.required' => 'قیمت را وارد کنید یا گزینه «تماس بگیرید» را انتخاب کنید.',
            'city_id.exists' => 'شهر انتخاب‌شده با استان انتخابی مطابقت ندارد.',
        ];
    }
}
