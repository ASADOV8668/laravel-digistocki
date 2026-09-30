<?php

namespace App\Http\Requests\Admin;

use App\Support\MobileNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mobile' => MobileNumber::normalize($this->input('mobile')),
            'is_active' => $this->boolean('is_active'),
            'can_post_listings' => $this->boolean('can_post_listings'),
        ]);
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'mobile' => ['required', 'regex:/^09\d{9}$/', Rule::unique('users', 'mobile')->ignore($user)],
            'national_id' => ['nullable', 'digits:10', Rule::unique('users', 'national_id')->ignore($user)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['user', 'admin'])],
            'is_active' => ['boolean'],
            'can_post_listings' => ['boolean'],
        ];
    }
}
