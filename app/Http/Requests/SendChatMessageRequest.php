<?php

namespace App\Http\Requests;

use App\Services\SystemOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SendChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $maxMb = app(SystemOptions::class)->maxImageUploadMb();

        return [
            'body' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:'.($maxMb * 1024)],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (trim((string) $this->input('body')) === '' && ! $this->hasFile('image')) {
                $validator->errors()->add('body', 'متن یا تصویر پیام را وارد کنید.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'body.max' => 'متن پیام نباید بیشتر از ۵۰۰۰ کاراکتر باشد.',
            'image.mimes' => 'فرمت تصویر باید JPG، PNG، WEBP یا HEIC باشد.',
            'image.max' => 'حجم تصویر نباید بیشتر از '.app(SystemOptions::class)->maxImageUploadMb().' مگابایت باشد.',
        ];
    }
}
