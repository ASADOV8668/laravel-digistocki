<?php

namespace App\Http\Requests\Auth;

use App\Support\MobileNumber;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'password' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $mobile = MobileNumber::normalize($this->input('mobile') ?? $this->input('login'));
        $this->merge(['mobile' => $mobile ?? trim((string) ($this->input('mobile') ?? $this->input('login')))]);
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! auth()->attempt(['mobile' => $this->string('mobile')->toString(), 'password' => $this->string('password')->toString(), 'is_active' => true], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages(['mobile' => trans('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));
        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages(['mobile' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)])]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('mobile')->toString()).'|'.$this->ip());
    }
}
