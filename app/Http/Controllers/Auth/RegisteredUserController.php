<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MobileOtpService;
use App\Services\SystemOptions;
use App\Support\MobileNumber;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request, SystemOptions $options): View
    {
        $step = $request->session()->has('registration_mobile_verified') ? 'profile' : ($request->session()->has('registration_mobile') ? 'otp' : 'mobile');

        return view('auth.register', [
            'registrationMode' => $options->registrationMode(),
            'step' => $step,
            'mobile' => $request->session()->get('registration_mobile_verified', $request->session()->get('registration_mobile')),
            'otpExpiresAt' => $request->session()->get('registration_otp_expires_at'),
        ]);
    }

    public function store(Request $request, MobileOtpService $otpService): RedirectResponse
    {
        if (! $request->session()->has('registration_mobile_verified')) {
            $request->merge(['mobile' => MobileNumber::normalizeLocal($request->input('mobile'))]);
            $validated = $request->validate(
                ['mobile' => ['required', 'regex:/^09\d{9}$/']],
                ['mobile.regex' => 'شماره موبایل باید دقیقاً ۱۱ رقم و با ۰۹ شروع شود؛ استفاده از ۹۸ یا + مجاز نیست.']
            );
            if (User::query()->where('mobile', $validated['mobile'])->exists()) {
                return redirect()->route('login')->withErrors(['mobile' => 'این شماره قبلاً ثبت شده است؛ وارد شوید.']);
            }

            $otp = $otpService->issue($validated['mobile'], 'register');
            if (! $otp) {
                return back()->withErrors(['mobile' => 'پیامک ارسال نشد؛ لطفاً بعداً دوباره تلاش کنید.']);
            }

            $request->session()->put(['registration_mobile' => $validated['mobile'], 'registration_otp_expires_at' => $otp->expires_at->timestamp]);

            return redirect()->route('register')->with('status', 'کد تأیید ثبت‌نام به موبایل شما ارسال شد.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'national_id' => ['nullable', 'digits:10', Rule::unique(User::class, 'national_id')],
        ]);
        $mobile = $request->session()->get('registration_mobile_verified');
        $user = User::create([
            'name' => $validated['name'],
            'mobile' => $mobile,
            'national_id' => $validated['national_id'] ?? null,
            'password' => Hash::make(Str::random(40)),
            'mobile_verified_at' => now(),
        ]);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->forget(['registration_mobile', 'registration_mobile_verified', 'registration_otp_expires_at']);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function verifyOtp(Request $request, MobileOtpService $otpService): RedirectResponse
    {
        $request->merge(['mobile' => MobileNumber::normalizeLocal($request->input('mobile'))]);
        $validated = $request->validate(
            ['mobile' => ['required', 'regex:/^09\d{9}$/'], 'otp' => ['required', 'digits_between:5,6']],
            [
                'mobile.regex' => 'شماره موبایل باید دقیقاً ۱۱ رقم و با ۰۹ شروع شود؛ استفاده از ۹۸ یا + مجاز نیست.',
                'otp.digits_between' => 'کد تأیید باید ۵ رقم باشد.',
            ]
        );
        abort_unless($request->session()->get('registration_mobile') === $validated['mobile'], 422, 'درخواست OTP معتبر نیست.');

        if (! $otpService->verify($validated['mobile'], 'register', $validated['otp'])) {
            return back()->withErrors(['otp' => 'کد واردشده نادرست یا منقضی شده است.']);
        }

        $request->session()->put('registration_mobile_verified', $validated['mobile']);
        $request->session()->forget(['registration_mobile', 'registration_otp_expires_at']);

        return redirect()->route('register');
    }

    public function resendOtp(Request $request, MobileOtpService $otpService): RedirectResponse
    {
        $mobile = $request->session()->get('registration_mobile');

        if (! is_string($mobile) || $mobile === '') {
            return redirect()->route('register')->withErrors(['mobile' => 'فرآیند ثبت‌نام را از ابتدا شروع کنید.']);
        }

        $otp = $otpService->issue($mobile, 'register');
        if (! $otp) {
            return back()->withErrors(['otp' => 'پیامک ارسال نشد؛ لطفاً بعداً دوباره تلاش کنید.']);
        }

        $request->session()->put('registration_otp_expires_at', $otp->expires_at->timestamp);

        return redirect()->route('register')->with('status', 'کد تأیید جدید به موبایل شما ارسال شد.');
    }
}
