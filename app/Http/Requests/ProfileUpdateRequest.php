<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\MobileNumber;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                'required_without:mobile',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'mobile' => [
                'nullable',
                'string',
                'regex:/^09\d{9}$/',
                'required_without:email',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['mobile' => MobileNumber::normalize($this->input('mobile'))]);
    }
}
