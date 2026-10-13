<?php

namespace App\Http\Requests;

use App\Models\Listing;
use App\Models\PhoneModel;
use App\Services\ListingAttributeValidator;
use App\Services\SystemOptions;
use App\Support\ListingTitle;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $store = $this->user()?->storefront;
        $location = [];

        if ($store && ! $this->user()?->isAdmin()) {
            $location = [
                'province_id' => $store->province_id,
                'city_id' => $store->city_id,
            ];
        }

        $title = trim((string) $this->input('title'));
        if ($title === '') {
            $title = ListingTitle::generate(
                $this->integer('brand_id'),
                $this->integer('phone_model_id'),
                (array) $this->input('attributes', []),
            );
        }

        $this->merge(array_filter([
            ...$location,
            'title' => $title,
        ], static fn (mixed $value): bool => $value !== null));
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', Listing::class) ?? false;
    }

    public function rules(): array
    {
        $store = $this->user()?->storefront;
        $locationRequirement = ! $this->user()?->isAdmin() && $store?->province_id && $store?->city_id
            ? 'required'
            : 'nullable';
        $imageRules = ['nullable', 'array', 'max:'.app(SystemOptions::class)->maxImageUploadCount()];
        $cloneHasImages = $this->integer('clone') > 0
            && Listing::query()
                ->where('user_id', $this->user()?->id)
                ->whereKey($this->integer('clone'))
                ->whereHas('images')
                ->exists();
        if (! $this->user()?->isAdmin() && $store?->province_id && $store?->city_id && ! $cloneHasImages) {
            $imageRules = ['required', 'array', 'min:1', 'max:'.app(SystemOptions::class)->maxImageUploadCount()];
        }
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
            'province_id' => [$locationRequirement, 'integer', 'exists:provinces,id'],
            'city_id' => [$locationRequirement, 'integer', Rule::exists('cities', 'id')->where(fn ($query) => $query->where('province_id', $this->input('province_id')))],
            'attributes' => ['nullable', 'array'],
            'attributes.*' => ['nullable'],
            'images' => $imageRules,
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
            'images.required' => 'حداقل یک تصویر برای آگهی انتخاب کنید.',
            'images.min' => 'حداقل یک تصویر برای آگهی انتخاب کنید.',
            'images.max' => 'تعداد تصاویر انتخاب‌شده بیشتر از حد مجاز تنظیمات است.',
            'images.*.max' => 'حجم هر تصویر نباید بیشتر از '.app(SystemOptions::class)->maxImageUploadMb().' مگابایت باشد.',
            'city_id.exists' => 'شهر انتخاب‌شده با استان انتخابی مطابقت ندارد.',
        ];
    }
}
