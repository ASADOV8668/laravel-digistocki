<?php

namespace App\Http\Requests;

use App\Models\Listing;
use App\Models\PhoneModel;
use App\Services\ListingAttributeValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Services\SystemOptions;

class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('listing')) ?? false;
    }

    public function rules(): array
    {
        $listing = $this->route('listing');
        $existingImages = $listing instanceof Listing ? $listing->images()->count() : 0;
        $remainingImageSlots = max(0, 8 - $existingImages);

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
            'images' => ['nullable', 'array', 'max:'.$remainingImageSlots],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:'.(app(SystemOptions::class)->maxImageUploadMb() * 1024)],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
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
}
