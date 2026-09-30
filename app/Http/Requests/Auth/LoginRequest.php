<?php

namespace App\Http\Requests\Auth;

use App\Services\SystemOptions;
use App\Support\MobileNumber;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = ['required', 'string', 'max:255'];
        if (app(SystemOptions::class)->registrationMode() === 'mobile') {
            $rules[] = 'regex:/^09\d{9}$/';
        }

        return [
            'login' => $rules,
            'password' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $login = trim((string) ($this->input('login') ?? $this->input('email')));
        if (app(SystemOptions::class)->registrationMode() === 'mobile') {
            $login = MobileNumber::normalize($login) ?? $login;
        }

        $this->merge(['login' => $login]);
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = $this->string('login')->toString();
        $field = app(SystemOptions::class)->registrationMode() === 'mobile' ? 'mobile' : (filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile');
        $value = $field === 'mobile' ? MobileNumber::normalize($login) : strtolower($login);
        $credentials = [$field => $value, 'password' => $this->string('password')->toString(), 'is_active' => true];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $login = $this->string('login')->toString();
        $identifier = app(SystemOptions::class)->registrationMode() === 'mobile'
            ? MobileNumber::normalize($login) ?? $login
            : (filter_var($login, FILTER_VALIDATE_EMAIL)
            ? strtolower($login)
            : (MobileNumber::normalize($login) ?? $login));

        return Str::transliterate(Str::lower($identifier).'|'.$this->ip());
    }
}
